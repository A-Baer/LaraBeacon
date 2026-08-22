<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class AppDebugAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(AppDebugAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_app_debug_in_production()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);

        $this->runLaraBeacon();

        $this->assertFailedAt(AppDebugAnalyzer::class, $this->getConfigStubPath('app'), 42);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_app_debug_in_local()
    {
        $this->app->config->set('app.env', 'local');
        $this->app->config->set('app.debug', true);

        $this->runLaraBeacon();

        $this->assertPassed(AppDebugAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_without_app_debug()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', false);

        $this->runLaraBeacon();

        $this->assertPassed(AppDebugAnalyzer::class);
    }
}
