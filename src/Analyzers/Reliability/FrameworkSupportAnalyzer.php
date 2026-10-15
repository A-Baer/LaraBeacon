<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\EvaluatesSupportWindow;
use Illuminate\Contracts\Foundation\Application;

class FrameworkSupportAnalyzer extends ReliabilityAnalyzer
{
    use EvaluatesSupportWindow;

    /**
     * Security support end dates from the official Laravel support policy.
     *
     * @var array<int, string>
     */
    const SECURITY_SUPPORT_ENDS = [
        10 => '2025-02-04',
        11 => '2026-03-12',
        12 => '2027-02-24',
        13 => '2028-03-17',
    ];

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your Laravel version receives security fixes for the foreseeable future.';

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
    public $timeToFix = 240;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * An approaching end of support is a release-planning signal and should
     * not suddenly fail unrelated pull requests.
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
        return $this->supportWindowMessage('Laravel');
    }

    /**
     * Execute the analyzer.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function handle(Application $app)
    {
        $major = (int) explode('.', $app->version())[0];

        if ($major < min(array_keys(self::SECURITY_SUPPORT_ENDS))) {
            $this->evaluateSupportWindow($major.'.x', '1970-01-01');

            return;
        }

        $this->evaluateSupportWindow($major.'.x', self::SECURITY_SUPPORT_ENDS[$major] ?? null);
    }
}
