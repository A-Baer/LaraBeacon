<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\EvaluatesSupportWindow;

class PhpSupportAnalyzer extends ReliabilityAnalyzer
{
    use EvaluatesSupportWindow;

    /**
     * Security support end dates from the official PHP supported versions page.
     *
     * @var array<string, string>
     */
    const SECURITY_SUPPORT_ENDS = [
        '8.1' => '2025-12-31',
        '8.2' => '2026-12-31',
        '8.3' => '2027-12-31',
        '8.4' => '2028-12-31',
        '8.5' => '2029-12-31',
    ];

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your PHP version receives security fixes for the foreseeable future.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MAJOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 120;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * The CI runtime rarely matches the production PHP version.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return $this->supportWindowMessage('PHP');
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $release = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        if (version_compare($release, '8.1', '<')) {
            $this->evaluateSupportWindow($release, '1970-01-01');

            return;
        }

        $this->evaluateSupportWindow($release, self::SECURITY_SUPPORT_ENDS[$release] ?? null);
    }
}
