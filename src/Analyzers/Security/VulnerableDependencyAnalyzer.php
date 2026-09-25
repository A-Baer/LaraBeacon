<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Composer;
use Throwable;

class VulnerableDependencyAnalyzer extends SecurityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not rely on backend dependencies with known security issues.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_CRITICAL;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 60;

    /**
     * The result of the vulnerability scan.
     *
     * @var array
     */
    public $result;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has a total of ".$this->vulnerabilityCount()." known vulnerabilities in the application "
            ."dependencies. Resolve these by either applying patch updates or "
            ."removing the vulnerable dependencies. The packages which have these vulnerabilities include: "
            .PHP_EOL.$this->listVulnerablePackages();
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Composer $composer
     * @return void
     */
    public function handle(Composer $composer)
    {
        $audit = $composer->audit();
        $versions = $this->lockedVersions($composer->getLockFile());

        $this->result = collect($audit['advisories'])
            ->map(fn ($advisories, $package) => [
                'version' => $versions[$package] ?? 'unknown',
                'advisories' => array_values($advisories),
            ])->all();

        if ($this->vulnerabilityCount() > 0) {
            $this->markFailed();
        }
    }

    /**
     * Read installed package versions from composer.lock.
     *
     * @return array<string, string>
     */
    protected function lockedVersions(?string $lockFile): array
    {
        if ($lockFile === null || ! is_file($lockFile)) {
            return [];
        }

        $lock = json_decode((string) file_get_contents($lockFile), true);

        return collect(array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []))
            ->mapWithKeys(fn ($package) => [$package['name'] => $package['version']])
            ->all();
    }

    protected function vulnerabilityCount(): int
    {
        return collect($this->result)
            ->sum(fn ($vulnerability) => count($vulnerability['advisories'] ?? []));
    }

    /**
     * List the vulnerable packages.
     *
     * @return string
     */
    public function listVulnerablePackages()
    {
        try {
            return collect($this->result)
                ->map(function ($vulnerability, $package) {
                    return $package.' ('.$vulnerability['version'].'): '.
                        collect(data_get($vulnerability, 'advisories.*.title'))
                        ->join(', ', ' and ');
                })->values()->implode(PHP_EOL);
        } catch (Throwable $e) {
            return json_encode($this->result);
        }
    }
}
