<?php

declare(strict_types=1);

namespace LaraBeaconMcp;

/**
 * Read-only facts about the Laravel project, gathered without booting the application.
 */
final class Project
{
    public const CORE_PACKAGE = 'baer-software/larabeacon';

    public const PRO_PACKAGE = 'baer-software/larabeacon-pro';

    /** @var array<string, string>|null */
    private ?array $installed = null;

    public function __construct(public readonly string $root) {}

    public static function resolve(?string $argument, ?string $environment, string $cwd): self
    {
        foreach ([$argument, $environment, $cwd] as $candidate) {
            // Claude Code leaves unknown ${VAR} placeholders unexpanded.
            if ($candidate === null || $candidate === '' || str_contains($candidate, '${')) {
                continue;
            }

            $directory = realpath($candidate);

            if ($directory === false || ! is_dir($directory)) {
                continue;
            }

            return new self(self::laravelRoot($directory) ?? $directory);
        }

        return new self($cwd);
    }

    /**
     * Walk up from a subdirectory to the directory that contains artisan and composer.json.
     */
    private static function laravelRoot(string $directory): ?string
    {
        for ($depth = 0; $depth < 8; $depth++) {
            if (is_file($directory.'/artisan') && is_file($directory.'/composer.json')) {
                return $directory;
            }

            $parent = dirname($directory);

            if ($parent === $directory) {
                break;
            }

            $directory = $parent;
        }

        return null;
    }

    public function isLaravel(): bool
    {
        return is_file($this->root.'/artisan') && $this->requires('laravel/framework');
    }

    public function hasVendor(): bool
    {
        return is_file($this->root.'/vendor/autoload.php');
    }

    /**
     * Whether composer.json requires the package (require or require-dev).
     */
    public function requires(string $package): bool
    {
        $composer = $this->json('composer.json');

        return isset($composer['require'][$package]) || isset($composer['require-dev'][$package]);
    }

    public function version(string $package): ?string
    {
        return $this->installedPackages()[$package] ?? null;
    }

    public function installed(string $package): bool
    {
        return $this->version($package) !== null;
    }

    /**
     * Installed version is at least $minimum. Development branches are assumed to be current.
     */
    public function atLeast(string $package, string $minimum): bool
    {
        $version = $this->version($package);

        if ($version === null) {
            return false;
        }

        $normalized = ltrim($version, 'vV');

        if (! preg_match('/^\d+\.\d+(\.\d+)?/', $normalized, $match)) {
            return true;
        }

        return version_compare($match[0], $minimum, '>=');
    }

    /**
     * Forget cached package versions after Composer changed vendor/.
     */
    public function refresh(): void
    {
        $this->installed = null;
    }

    /**
     * Package name => version from composer.lock (packages and packages-dev).
     *
     * @return array<string, string>
     */
    public function lockedVersions(): array
    {
        $lock = $this->json('composer.lock');
        $versions = [];

        foreach ([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])] as $package) {
            if (is_array($package) && is_string($package['name'] ?? null)) {
                $versions[$package['name']] = (string) ($package['version'] ?? '');
            }
        }

        return $versions;
    }

    public function hasFile(string $relativePath): bool
    {
        return is_file($this->root.'/'.$relativePath);
    }

    /**
     * Resolve a user-supplied relative path that must stay inside the project.
     */
    public function relativePath(string $path, bool $mustExist): string
    {
        $path = str_replace('\\', '/', trim($path));

        if ($path === '' || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) || in_array('..', explode('/', $path), true)) {
            throw new ToolError('Use a path relative to the project root without "..".');
        }

        if ($mustExist && ! is_file($this->root.'/'.$path)) {
            throw new ToolError('File not found in the project: '.$path);
        }

        return $path;
    }

    /** @return array<string, string> */
    private function installedPackages(): array
    {
        if ($this->installed !== null) {
            return $this->installed;
        }

        $this->installed = [];
        $data = $this->json('vendor/composer/installed.json');
        $packages = $data['packages'] ?? $data;

        if (is_array($packages)) {
            foreach ($packages as $package) {
                if (is_array($package) && is_string($package['name'] ?? null)) {
                    $this->installed[$package['name']] = (string) ($package['version'] ?? 'unknown');
                }
            }
        }

        return $this->installed;
    }

    private function json(string $relativePath): array
    {
        $file = $this->root.'/'.$relativePath;

        if (! is_file($file) || filesize($file) > 20_000_000) {
            return [];
        }

        $data = json_decode((string) file_get_contents($file), true);

        return is_array($data) ? $data : [];
    }
}

final class ToolError extends \RuntimeException {}
