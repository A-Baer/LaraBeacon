<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;

class CustomCategoryStub extends Analyzer
{
    /**
     * The category of the analyzer.
     *
     * @var string|null
     */
    public $category = 'Custom';
}
