<?php

namespace BaerSoftware\LaraBeacon;

use BaerSoftware\LaraBeacon\Analyzers\Trace;
use Composer\InstalledVersions;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class PHPStan
{
    /**
     * The PHPStan analysis result.
     *
     * @var array
     */
    public $result;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The project root path.
     *
     * @var string|null
     */
    protected $rootPath;

    /**
     * The PHPStan configuration file path.
     *
     * @var string|null
     */
    protected $configPath;

    /**
     * The isolated PHPStan cache directory for the current run.
     *
     * @var string|null
     */
    protected $runtimeTempPath;

    /**
     * Create a new PHPStan manager instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string|null  $rootPath
     * @return void
     */
    public function __construct(Filesystem $files, $rootPath = null)
    {
        $this->files = $files;
        $this->rootPath = $rootPath;
    }

    /**
     * Parse the PHPStan analysis and get the results containing the search string.
     *
     * @param string|array $search
     * @return array
     */
    public function parseAnalysis($search)
    {
        if (! isset($this->result['files'])) {
            return [];
        }

        return collect($this->result['files'])->map(function ($fileAnalysis, $path) use ($search) {
            return collect($fileAnalysis['messages'])->filter(function ($message) use ($search) {
                return Str::contains($message['message'], $search);
            })->map(function ($message) use ($path) {
                return new Trace($path, $message['line'], $message['message']);
            })->flatten()->toArray();
        })->filter()->flatten()->toArray();
    }

    /**
     * Parse the PHPStan analysis and get the results matching the pattern.
     *
     * @param  string|array  $pattern
     * @return array
     */
    public function match($pattern)
    {
        if (! isset($this->result['files'])) {
            return [];
        }

        return collect($this->result['files'])->map(function ($fileAnalysis, $path) use ($pattern) {
            return collect($fileAnalysis['messages'])->filter(function ($message) use ($pattern) {
                return Str::is($pattern, $message['message']);
            })->map(function ($message) use ($path) {
                return new Trace($path, $message['line'], $message['message']);
            })->flatten()->toArray();
        })->filter()->flatten()->toArray();
    }

    /**
     * Parse the PHPStan analysis and get the results matching the pattern.
     *
     * @param  string|array  $pattern
     * @return array
     */
    public function pregMatch($pattern)
    {
        if (! isset($this->result['files'])) {
            return [];
        }

        return collect($this->result['files'])->map(function ($fileAnalysis, $path) use ($pattern) {
            return collect($fileAnalysis['messages'])->filter(function ($message) use ($pattern) {
                return preg_match($pattern, $message['message']) === 1;
            })->map(function ($message) use ($path) {
                return new Trace($path, $message['line'], $message['message']);
            })->flatten()->toArray();
        })->filter()->flatten()->toArray();
    }

    /**
     * Run the PHPStan analysis and get the output
     *
     * @param  string|array  $paths
     * @param  string|null  $configPath
     * @return $this
     */
    public function start($paths, $configPath = null)
    {
        $runtimeConfigPath = null;
        $configPath = $configPath ?? $this->configPath;

        if (is_null($configPath)) {
            $configPath = $runtimeConfigPath = $this->createRuntimeConfig();
        }

        $options = ['analyse', '--configuration='.$configPath];

        $options = array_merge($options, $this->getPHPStanOptions());

        foreach (Arr::wrap($paths) as $path) {
            $options[] = $path;
        }

        try {
            $output = $this->runCommand($options);
            $this->result = $this->decodeResult($output);

            if (is_null($this->result)) {
                throw new RuntimeException('PHPStan did not return a valid JSON result: '.trim($output));
            }
        } finally {
            if (! is_null($runtimeConfigPath)) {
                $this->files->delete($runtimeConfigPath);
            }

            if (! is_null($this->runtimeTempPath)) {
                $this->files->deleteDirectory($this->runtimeTempPath);
                $this->runtimeTempPath = null;
            }
        }

        return $this;
    }

    /**
     * Build a temporary PHPStan configuration using the host application's
     * Composer dependencies. Package-relative vendor paths do not exist when
     * LaraBeacon itself is installed below vendor/baer-software.
     */
    protected function createRuntimeConfig(): string
    {
        $paths = [
            $this->packageInstallPath('larastan/larastan').'/extension.neon',
            $this->packageInstallPath('phpstan/phpstan-deprecation-rules').'/rules.neon',
            realpath(__DIR__.'/../phpstan.neon'),
        ];

        $configPath = sys_get_temp_dir().'/larabeacon-phpstan-'.bin2hex(random_bytes(12)).'.neon';
        $this->runtimeTempPath = sys_get_temp_dir().'/larabeacon-phpstan-cache-'.bin2hex(random_bytes(12));
        $contents = "includes:\n".collect($paths)
            ->map(fn ($path) => '    - '.json_encode($path, JSON_THROW_ON_ERROR))
            ->join("\n")."\nparameters:\n    tmpDir: ".json_encode(
                $this->runtimeTempPath,
                JSON_THROW_ON_ERROR
            )."\n";

        $this->files->put($configPath, $contents);

        return $configPath;
    }

    /**
     * Decode PHPStan's JSON result, allowing for informational output that
     * newer PHPStan versions may print before the JSON payload.
     */
    protected function decodeResult(string $output): ?array
    {
        $result = json_decode($output, true);

        if (is_array($result)) {
            return $result;
        }

        foreach (array_reverse(preg_split('/\R/', trim($output)) ?: []) as $line) {
            $result = json_decode($line, true);

            if (is_array($result)) {
                return $result;
            }
        }

        $length = strlen($output);
        $start = null;
        $depth = 0;
        $inString = false;
        $escaped = false;

        for ($index = 0; $index < $length; $index++) {
            $character = $output[$index];

            if (is_null($start)) {
                if ($character === '{') {
                    $start = $index;
                    $depth = 1;
                }

                continue;
            }

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($character === '"') {
                $inString = true;
            } elseif ($character === '{') {
                $depth++;
            } elseif ($character === '}') {
                $depth--;

                if ($depth === 0) {
                    $result = json_decode(substr($output, $start, $index - $start + 1), true);

                    if (is_array($result)) {
                        return $result;
                    }

                    $start = null;
                }
            }
        }

        return null;
    }

    /**
     * Run any PHPStan command and get the output
     *
     * @param  array  $options
     * @param  bool  $includeErrorOutput
     * @return string
     */
    public function runCommand(array $options = [], $includeErrorOutput = true)
    {
        $phpStan = $this->findPHPStan();

        $command = array_merge((array) $phpStan, $options);

        $process = $this->getProcess($command);

        $process->run();

        return $process->getOutput().($includeErrorOutput ? $process->getErrorOutput() : '');
    }

    /**
     * Set the PHPStan configuration file path.
     *
     * @param string $configPath
     * @return $this
     */
    public function setConfigPath(string $configPath)
    {
        $this->configPath = $configPath;

        return $this;
    }

    /**
     * Get the PHPStan command for the environment.
     *
     * @return array
     */
    protected function findPHPStan()
    {
        return [PHP_BINARY, $this->packageInstallPath('phpstan/phpstan').'/phpstan.phar'];
    }

    /**
     * Resolve a dependency independently of Composer's configured vendor path.
     */
    protected function packageInstallPath(string $package): string
    {
        $path = InstalledVersions::getInstallPath($package);

        if (is_null($path)) {
            throw new RuntimeException("Unable to locate the installed Composer package [{$package}].");
        }

        return $path;
    }

    /**
     * Get a new Symfony process instance.
     *
     * @param  array  $command
     * @return \Symfony\Component\Process\Process
     */
    protected function getProcess(array $command)
    {
        return (new Process($command, $this->rootPath))->setTimeout(null);
    }

    /**
     * Set the root path used by the class.
     *
     * @param  string  $path
     * @return $this
     */
    public function setRootPath(string $path)
    {
        $this->rootPath = realpath($path);

        return $this;
    }

    /**
     * Get default PHPStan runtime configurations.
     *
     * @return array
     */
    protected function getPHPStanOptions()
    {
        $result = [];

        $configs = config('larabeacon.phpstan', ['--error-format' => 'json', '--no-progress' => true]);

        foreach ($configs as $name => $value) {
            $option = is_bool($value) ? $name : implode('=', [$name, $value]);
            array_push($result, $option);
        }

        return $result;
    }
}
