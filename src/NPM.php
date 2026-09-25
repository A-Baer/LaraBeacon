<?php

namespace BaerSoftware\LaraBeacon;

use Illuminate\Filesystem\Filesystem;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class NPM
{
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
     * Determine whether the command is npm or yarn.
     *
     * @var bool
     */
    protected $isYarn = false;

    /**
     * Determine whether the command is pnpm.
     *
     * @var bool
     */
    protected $isPnpm = false;

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
     * Count the frontend vulnerabilities.
     *
     * @param bool $excludeDev
     * @return int
     */
    public function countVulnerabilities($excludeDev = true)
    {
        return array_sum($this->vulnerabilitySummary($excludeDev));
    }

    /**
     * Return vulnerability counts grouped by severity.
     *
     * @param bool $excludeDev
     * @return array<string, int>
     */
    public function vulnerabilitySummary($excludeDev = true)
    {
        $auditResult = $this->audit($excludeDev);

        return collect($auditResult['metadata']['vulnerabilities']
            ?? $auditResult['data']['vulnerabilities'] ?? [])
            ->filter(function ($_, $type) {
                return ! in_array($type, ['total', 'info']);
            })->map(fn ($count) => (int) $count)->all();
    }

    /**
     * Run the NPM audit command and get the vulnerabilities.
     *
     * @param bool $excludeDev
     * @return array
     */
    public function audit($excludeDev = true)
    {
        // Detect the package manager before building options because each tool
        // uses a different option for excluding development dependencies.
        $this->findNpmOrYarn();
        $options = match (true) {
            $this->isPnpm && $excludeDev => ['audit', '--prod', '--json'],
            $this->isYarn || $this->isPnpm => ['audit', '--json'],
            $excludeDev => ['audit', '--omit=dev', '--json'],
            default => ['audit', '--json'],
        };
        $output = $this->runCommand($options, false);
        $result = json_decode($output, true);

        if (is_array($result) && $this->hasVulnerabilitySummary($result)) {
            return $result;
        }

        // Yarn Classic emits newline-delimited JSON events. Its auditSummary
        // event has the same data.vulnerabilities shape supported above.
        $summary = collect(preg_split('/\R/', trim((string) $output)) ?: [])
            ->map(fn ($line) => json_decode($line, true))
            ->filter(fn ($event) => is_array($event) && ($event['type'] ?? null) === 'auditSummary')
            ->last();

        if (is_array($summary) && $this->hasVulnerabilitySummary($summary)) {
            return $summary;
        }

        throw new RuntimeException('The package manager did not return a valid vulnerability audit summary.');
    }

    protected function hasVulnerabilitySummary(array $result): bool
    {
        $summary = $result['metadata']['vulnerabilities'] ?? $result['data']['vulnerabilities'] ?? null;

        if (! is_array($summary) || $summary === []) {
            return false;
        }

        $allowedKeys = ['info', 'low', 'moderate', 'high', 'critical', 'total'];
        $severityKeys = ['info', 'low', 'moderate', 'high', 'critical'];

        if (array_diff(array_keys($summary), $allowedKeys) !== []
            || array_intersect(array_keys($summary), $severityKeys) === []) {
            return false;
        }

        return collect($summary)->every(fn ($count) => is_int($count) && $count >= 0);
    }

    /**
     * Run any npm or yarn command and get the output.
     *
     * @param  array  $options
     * @param  bool  $includeErrorOutput
     * @return string|null
     */
    public function runCommand(array $options = [], $includeErrorOutput = true)
    {
        $npm = $this->findNpmOrYarn();

        if (empty($npm)) {
            return null;
        }

        $command = array_merge((array) $npm, $options);

        $process = $this->getProcess($command);

        $process->run();

        return $process->getOutput().($includeErrorOutput ? $process->getErrorOutput() : '');
    }

    /**
     * Get the npm or yarn command for the environment.
     *
     * @return array
     */
    public function findNpmOrYarn()
    {
        $this->isYarn = false;
        $this->isPnpm = false;

        if (! $this->files->exists($this->rootPath.'/package.json')) {
            return [];
        }

        $packageJson = json_decode((string) $this->files->get($this->rootPath.'/package.json'), true);
        $packageManager = $packageJson['packageManager'] ?? null;

        if (is_string($packageManager) && str_starts_with($packageManager, 'yarn@')) {
            if (! $this->commandExists('yarn')) {
                return [];
            }

            $this->isYarn = true;

            return ['yarn'];
        }

        if (is_string($packageManager) && str_starts_with($packageManager, 'npm@')) {
            return $this->commandExists('npm') ? ['npm'] : [];
        }

        if (is_string($packageManager) && str_starts_with($packageManager, 'pnpm@')) {
            if (! $this->commandExists('pnpm')) {
                return [];
            }

            $this->isPnpm = true;

            return ['pnpm'];
        }

        $hasYarnLock = $this->files->exists($this->rootPath.'/yarn.lock');
        $hasPnpmLock = $this->files->exists($this->rootPath.'/pnpm-lock.yaml');
        $hasNpmLock = $this->files->exists($this->rootPath.'/package-lock.json')
            || $this->files->exists($this->rootPath.'/npm-shrinkwrap.json');

        if (count(array_filter([$hasYarnLock, $hasPnpmLock, $hasNpmLock])) > 1) {
            return [];
        }

        if ($hasYarnLock) {
            if ($this->commandExists('yarn')) {
                $this->isYarn = true;

                return ['yarn'];
            }
        }

        if ($hasPnpmLock) {
            if ($this->commandExists('pnpm')) {
                $this->isPnpm = true;

                return ['pnpm'];
            }

            return [];
        }

        if ($hasNpmLock) {
            if ($this->commandExists('npm')) {
                return ['npm'];
            }
        }

        if ($this->commandExists('npm')) {
            return ['npm'];
        }

        if ($this->commandExists('yarn')) {
            $this->isYarn = true;

            return ['yarn'];
        }

        if ($this->commandExists('pnpm')) {
            $this->isPnpm = true;

            return ['pnpm'];
        }

        return [];
    }

    /**
     * Get the npm or yarn command for the environment.
     *
     * @param string $command
     * @return bool
     */
    protected function commandExists(string $command)
    {
        return (new ExecutableFinder())->find($command) !== null;
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
}
