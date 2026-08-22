<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\RouteCachingAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class RouteCachingAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(RouteCachingAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_cached_routes_in_local()
    {
        $this->app->config->set('app.env', 'local');
        
        $this->app->instance('routes.cached', true);

        $this->runLaraBeacon();

        $this->assertFailed(RouteCachingAnalyzer::class);
        
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_non_cached_routes_in_production()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->instance('routes.cached', false);
        
        $this->runLaraBeacon();

        $this->assertFailed(RouteCachingAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_cached_routes_in_production()
    {
        $this->app->config->set('app.env', 'production');
        
        $this->app->instance('routes.cached', true);

        $this->runLaraBeacon();

        $this->assertPassed(RouteCachingAnalyzer::class);

    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_non_cached_routes_in_local()
    {
        $this->app->config->set('app.env', 'local');
        $this->app->instance('routes.cached', false);
        
        $this->runLaraBeacon();

        $this->assertPassed(RouteCachingAnalyzer::class);
    }
}
