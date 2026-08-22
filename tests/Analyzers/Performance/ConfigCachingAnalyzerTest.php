<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\ConfigCachingAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class ConfigCachingAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(ConfigCachingAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_cached_config_in_local()
    {
        $this->app->config->set('app.env', 'local');

        $this->app->instance('config_loaded_from_cache', true);

        $this->runLaraBeacon();

        $this->assertFailed(ConfigCachingAnalyzer::class);

    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_non_cached_config_in_production()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->instance('config_loaded_from_cache', false);

        $this->runLaraBeacon();

        $this->assertFailed(ConfigCachingAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_cached_config_in_production()
    {
        $this->app->config->set('app.env', 'production');

        $this->app->instance('config_loaded_from_cache', true);

        $this->runLaraBeacon();

        $this->assertPassed(ConfigCachingAnalyzer::class);

    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_non_cached_config_in_local()
    {
        $this->app->config->set('app.env', 'local');
        $this->app->instance('config_loaded_from_cache', false);

        $this->runLaraBeacon();

        $this->assertPassed(ConfigCachingAnalyzer::class);
    }
}
