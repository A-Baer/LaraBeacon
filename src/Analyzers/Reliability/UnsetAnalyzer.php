<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\ParsesPHPStanAnalysis;
use BaerSoftware\LaraBeacon\PHPStan;

class UnsetAnalyzer extends ReliabilityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application does not try to unset undefined variables.";

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
    public $timeToFix = 5;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application seems to unset undefined variables.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\PHPStan $PHPStan
     * @return void
     */
    public function handle(PHPStan $PHPStan)
    {
        $this->matchPHPStanAnalysis($PHPStan, [
            'Call to function unset* undefined variable*', 'Cannot unset offset *',
        ]);
    }
}
