<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\ParsesPHPStanAnalysis;
use BaerSoftware\LaraBeacon\PHPStan;

class UndefinedConstantAnalyzer extends ReliabilityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application does not rely on undefined constants.";

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
        return "Your application seems to reference undefined constants.";
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
            '* undefined constant *', 'Using * outside of class scope*', 'Access to constant * on an unknown class *',
        ]);
    }
}
