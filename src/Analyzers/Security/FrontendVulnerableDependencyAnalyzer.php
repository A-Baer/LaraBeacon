<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\NPM;

class FrontendVulnerableDependencyAnalyzer extends SecurityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not rely on frontend dependencies with known security issues.';

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
     * The NPM instance.
     *
     * @var \BaerSoftware\LaraBeacon\NPM
     */
    private $NPM;

    /**
     * The number of frontend vulnerabilities.
     *
     * @var int
     */
    protected $vulnerabilityCount;

    /** @var array<string, int> */
    protected $vulnerabilitySummary = [];

    /**
     * Create a new analyzer instance.
     *
     * @param \BaerSoftware\LaraBeacon\NPM $NPM
     */
    public function __construct(NPM $NPM)
    {
        $this->NPM = $NPM;
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "The audited dependency graph contains {$this->vulnerabilityCount} advisories reported by npm, pnpm "
            ."or Yarn ({$this->formatVulnerabilitySummary()}). npm and pnpm scans omit declared development "
            ."dependencies; Yarn support depends on the installed version. Findings may still involve browser runtime "
            ."code, server-side JavaScript or build tooling, so review reachability and deployment impact before "
            ."prioritising fixes. Run the detected package manager's audit command for package-level details.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->vulnerabilitySummary = $this->NPM->vulnerabilitySummary();
        $this->vulnerabilityCount = array_sum($this->vulnerabilitySummary);

        if ($this->vulnerabilityCount > 0) {
            $this->severity = match (true) {
                ($this->vulnerabilitySummary['critical'] ?? 0) > 0 => self::SEVERITY_CRITICAL,
                ($this->vulnerabilitySummary['high'] ?? 0) > 0 => self::SEVERITY_MAJOR,
                default => self::SEVERITY_MINOR,
            };

            $this->markFailed();
        }
    }

    protected function formatVulnerabilitySummary(): string
    {
        return collect($this->vulnerabilitySummary)
            ->filter()
            ->map(fn ($count, $severity) => $severity.': '.$count)
            ->implode(', ');
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip the analyzer if package.json or a supported package manager does not exist.
        return empty($this->NPM->findNpmOrYarn());
    }
}
