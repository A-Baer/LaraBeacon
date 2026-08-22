<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\ParsesPHPStanAnalysis;
use BaerSoftware\LaraBeacon\PHPStan;

class MissingReturnStatementAnalyzer extends ReliabilityAnalyzer
{
    use ParsesPHPStanAnalysis;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = "Your application does not contain missing return statements.";

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
        return "Your application seems to be missing some return statements.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\PHPStan $PHPStan
     * @return void
     */
    public function handle(PHPStan $PHPStan)
    {
        $this->parsePHPStanAnalysis($PHPStan, ['return statement is missing']);
    }
}
