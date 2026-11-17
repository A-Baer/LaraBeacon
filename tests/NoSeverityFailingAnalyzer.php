<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Performance\PerformanceAnalyzer;

class NoSeverityFailingAnalyzer extends PerformanceAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'A custom check without a severity.';

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        $this->markFailed();
    }
}
