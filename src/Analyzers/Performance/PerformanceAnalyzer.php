<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;

abstract class PerformanceAnalyzer extends Analyzer
{
    /**
     * The category of the analyzer.
     *
     * @var string|null
     */
    public $category = 'Performance';
}
