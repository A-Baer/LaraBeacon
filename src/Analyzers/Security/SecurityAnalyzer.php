<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;

abstract class SecurityAnalyzer extends Analyzer
{
    /**
     * The category of the analyzer.
     *
     * @var string|null
     */
    public $category = 'Security';
}
