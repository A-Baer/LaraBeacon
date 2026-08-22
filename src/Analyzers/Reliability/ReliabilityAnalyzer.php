<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;

abstract class ReliabilityAnalyzer extends Analyzer
{
    /**
     * The category of the analyzer.
     *
     * @var string|null
     */
    public $category = 'Reliability';
}
