<?php

declare(strict_types=1);

namespace LaraBeaconMcp;

/**
 * Detects outdated LaraBeacon packages and updates them through Composer within the project's constraints.
 */
final class Updates
{
    private const CACHE_SECONDS = 86_400;

    private const PACKAGIST = 'https://repo.packagist.org/p2/baer-software/larabeacon.json';

    public function __construct(
        private readonly Project $project,
        private readonly Artisan $runner,
        private readonly string $cacheFile,
        private readonly string $php,
        private readonly bool $networkEnabled = true,
    ) {}

    public static function cacheFile(): string
    {
        $directory = getenv('CLAUDE_PLUGIN_DATA') ?: sys_get_temp_dir().'/larabeacon-mcp';

        return rtrim($directory, '/').'/core-versions.json';
    }

    /**
     * Installed and newest released Core versions. Pro is private, so only its installed version is known.
     *
     * @return array{installed: ?string, latest: ?string, latest_compatible: ?string, update_available: bool, blocked_by_constraint: ?string, major_available: ?string, pro_installed: ?string}
     */
    public function check(): array
    {
        $installed = $this->project->version(Project::CORE_PACKAGE);
        $versions = $this->releasedVersions();
        $installedNumber = self::number($installed);
        $latest = $versions[0] ?? null;
        $compatible = null;

        if ($installedNumber !== null) {
            $major = strtok($installedNumber, '.');

            foreach ($versions as $version) {
                if (strtok($version, '.') === $major) {
                    $compatible = $version;
                    break;
                }
            }
        }

        $newer = $installedNumber !== null && $compatible !== null && version_compare($compatible, $installedNumber, '>');
        $blocked = $newer && $this->blocked() === ['installed' => $installedNumber, 'target' => $compatible];

        return [
            'installed' => $installed,
            'latest' => $latest,
            'latest_compatible' => $compatible,
            'update_available' => $newer && ! $blocked,
            'blocked_by_constraint' => $blocked ? $compatible : null,
            'major_available' => $installedNumber !== null && $latest !== null && strtok($latest, '.') !== strtok($installedNumber, '.') && version_compare($latest, $installedNumber, '>')
                ? $latest
                : null,
            'pro_installed' => $this->project->version(Project::PRO_PACKAGE),
        ];
    }

    /**
     * Run `composer update` for the installed LaraBeacon packages and report what changed in composer.lock.
     */
    public function update(): array
    {
        $target = $this->check()['latest_compatible'];
        $packages = [Project::CORE_PACKAGE];

        if ($this->project->installed(Project::PRO_PACKAGE)) {
            $packages[] = Project::PRO_PACKAGE;
        }

        $before = $this->project->lockedVersions();
        $result = $this->runner->process([...$this->composer(), 'update', ...$packages, '--with-dependencies', '--no-interaction', '--no-progress', '--no-ansi']);
        $this->project->refresh();
        $after = $this->project->lockedVersions();

        if ($result['exit'] !== 0) {
            throw new ToolError('composer update failed; composer.json and composer.lock were left as Composer left them. '.$this->runner->failureMessage($result));
        }

        $coreFrom = self::number($before[Project::CORE_PACKAGE] ?? null);
        $coreTo = self::number($after[Project::CORE_PACKAGE] ?? null);

        // composer.json does not allow the newer release: stop suggesting it until either side changes.
        if ($coreTo !== null && $coreFrom === $coreTo && $target !== null && version_compare($target, $coreTo, '>')) {
            $this->writeJson($this->blockedFile(), ['installed' => $coreTo, 'target' => $target]);
        }

        $changed = [];

        foreach ($after as $name => $version) {
            if (($before[$name] ?? null) !== $version) {
                $changed[] = ['package' => $name, 'from' => $before[$name] ?? null, 'to' => $version];
            }
        }

        foreach (array_diff_key($before, $after) as $name => $version) {
            $changed[] = ['package' => $name, 'from' => $version, 'to' => null];
        }

        return [
            'core_updated' => $coreFrom !== $coreTo,
            'core' => ['from' => $before[Project::CORE_PACKAGE] ?? null, 'to' => $after[Project::CORE_PACKAGE] ?? null],
            'pro' => in_array(Project::PRO_PACKAGE, $packages, true)
                ? ['from' => $before[Project::PRO_PACKAGE] ?? null, 'to' => $after[Project::PRO_PACKAGE] ?? null]
                : null,
            'changed_packages' => $changed,
            'next_step' => match (true) {
                $coreFrom === $coreTo && $target !== null && $coreTo !== null && version_compare($target, $coreTo, '>') => 'The constraint in composer.json does not allow LaraBeacon '.$target.'. Widen it (for example ^'.strtok($target, '.').'.0) if the user wants the newer release.',
                $changed === [] => 'Everything was already at the newest version allowed by composer.json.',
                default => 'Review and commit composer.lock. A new major version still needs a changed constraint in composer.json.',
            },
        ];
    }

    /**
     * Stable Core releases, newest first, from Packagist (cached for one day).
     *
     * @return list<string>
     */
    public function releasedVersions(): array
    {
        $cached = is_file($this->cacheFile) ? json_decode((string) file_get_contents($this->cacheFile), true) : null;

        $known = self::valid($cached['versions'] ?? null);

        if ($known !== [] && (time() - (int) ($cached['checked_at'] ?? 0)) < self::CACHE_SECONDS) {
            return $known;
        }

        if (! $this->networkEnabled) {
            return $known;
        }

        $context = stream_context_create(['http' => ['timeout' => 4, 'header' => "User-Agent: larabeacon-claude-plugin\r\n"]]);
        $body = @file_get_contents(self::PACKAGIST, false, $context);
        $data = is_string($body) ? json_decode($body, true) : null;
        $versions = [];

        foreach ($data['packages'][Project::CORE_PACKAGE] ?? [] as $release) {
            $number = self::number((string) ($release['version'] ?? ''));

            if ($number !== null && preg_match('/^\d+\.\d+\.\d+$/', $number)) {
                $versions[] = $number;
            }
        }

        if ($versions === []) {
            // Offline or Packagist unavailable: keep the last known list and retry tomorrow rather than on every call.
            $this->writeJson($this->cacheFile, ['checked_at' => time(), 'versions' => $known]);

            return $known;
        }

        usort($versions, fn (string $a, string $b): int => version_compare($b, $a));
        $versions = array_values(array_unique($versions));
        $this->writeJson($this->cacheFile, ['checked_at' => time(), 'versions' => $versions]);

        return $versions;
    }

    /** @return array{installed: string, target: string}|null */
    private function blocked(): ?array
    {
        $file = $this->blockedFile();
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($data) && is_string($data['installed'] ?? null) && is_string($data['target'] ?? null)
            ? ['installed' => $data['installed'], 'target' => $data['target']]
            : null;
    }

    private function blockedFile(): string
    {
        // One marker per project, next to the version cache.
        return dirname($this->cacheFile).'/blocked-'.substr(hash('sha256', $this->project->root), 0, 16).'.json';
    }

    private function writeJson(string $file, array $data): void
    {
        if (is_dir(dirname($file)) || @mkdir(dirname($file), 0700, true)) {
            @file_put_contents($file, json_encode($data));
        }
    }

    /** @return list<string> */
    private static function valid(mixed $versions): array
    {
        return is_array($versions)
            ? array_values(array_filter($versions, fn ($version): bool => is_string($version) && preg_match('/^\d+\.\d+\.\d+$/', $version) === 1))
            : [];
    }

    /** @return list<string> */
    private function composer(): array
    {
        $configured = getenv('LARABEACON_COMPOSER');

        if (is_string($configured) && $configured !== '') {
            return str_ends_with($configured, '.phar') ? [$this->php, $configured] : [$configured];
        }

        if ($this->project->hasFile('composer.phar')) {
            return [$this->php, 'composer.phar'];
        }

        return ['composer'];
    }

    private static function number(?string $version): ?string
    {
        if ($version === null || ! preg_match('/^v?(\d+\.\d+(\.\d+)?)$/', $version, $match)) {
            return null;
        }

        return $match[1];
    }
}
