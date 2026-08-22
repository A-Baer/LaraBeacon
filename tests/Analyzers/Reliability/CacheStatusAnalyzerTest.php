<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\CacheStatusAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class CacheStatusAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CacheStatusAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_default_file_driver()
    {
        $this->runLaraBeacon();

        $this->assertPassed(CacheStatusAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_non_existent_storage_path()
    {
        $this->app->config->set('cache.default', 'memcached');

        $this->runLaraBeacon();

        $this->assertFailed(CacheStatusAnalyzer::class);
    }
}
