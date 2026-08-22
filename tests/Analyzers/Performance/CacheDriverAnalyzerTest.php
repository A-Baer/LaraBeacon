<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\CacheDriverAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class CacheDriverAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CacheDriverAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_null_cache_driver()
    {
        $this->app->config->set('cache.default', 'null');

        $this->runLaraBeacon();

        $this->assertFailedAt(CacheDriverAnalyzer::class, $this->getConfigStubPath('cache'), 18);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_array_cache_driver()
    {
        $this->app->config->set('cache.default', 'array');

        $this->runLaraBeacon();

        $this->assertFailedAt(CacheDriverAnalyzer::class, $this->getConfigStubPath('cache'), 18);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_file_cache_driver_in_non_local_env()
    {
        $this->app->config->set('cache.default', 'file');
        $this->app->config->set('app.env', 'production');

        $this->runLaraBeacon();

        $this->assertFailedAt(CacheDriverAnalyzer::class, $this->getConfigStubPath('cache'), 18);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_file_cache_driver_in_local_env()
    {
        $this->app->config->set('cache.default', 'file');
        $this->app->config->set('app.env', 'local');

        $this->runLaraBeacon();

        $this->assertPassed(CacheDriverAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_database_cache_driver_in_non_local_env()
    {
        $this->app->config->set('cache.default', 'database');
        $this->app->config->set('app.env', 'production');

        $this->runLaraBeacon();

        $this->assertFailedAt(CacheDriverAnalyzer::class, $this->getConfigStubPath('cache'), 18);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_database_cache_driver_in_local_env()
    {
        $this->app->config->set('cache.default', 'database');
        $this->app->config->set('app.env', 'local');

        $this->runLaraBeacon();

        $this->assertPassed(CacheDriverAnalyzer::class);
    }
}
