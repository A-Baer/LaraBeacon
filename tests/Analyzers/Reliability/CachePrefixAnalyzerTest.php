<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\CachePrefixAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class CachePrefixAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CachePrefixAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_empty_cache_prefix()
    {
        $this->app->config->set('cache.prefix', '');

        $this->runLaraBeacon();

        $this->assertFailedAt(CachePrefixAnalyzer::class, $this->getConfigStubPath('cache'), 102);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_generic_cache_prefix()
    {
        $this->app->config->set('cache.prefix', 'laravel_cache');

        $this->runLaraBeacon();

        $this->assertFailedAt(CachePrefixAnalyzer::class, $this->getConfigStubPath('cache'), 102);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_specific_cache_prefix()
    {
        $this->app->config->set('cache.prefix', 'larabeacon_cache');

        $this->runLaraBeacon();

        $this->assertPassed(CachePrefixAnalyzer::class);
    }
}
