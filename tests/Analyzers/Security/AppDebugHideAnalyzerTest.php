<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugHideAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class AppDebugHideAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(
            AppDebugHideAnalyzer::class,
            $app,
            $this->getMockedAnalyzer(AppDebugHideAnalyzer::class)
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_empty_app_debug_hide_in_staging()
    {
        $this->app->config->set('app.env', 'staging');
        $this->app->config->set('app.debug', true);

        $this->runLaraBeacon();

        $this->assertFailedAt(AppDebugHideAnalyzer::class, $this->getConfigStubPath('app'), 42);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_debug_mode_off()
    {
        $this->app->config->set('app.debug', false);

        $this->runLaraBeacon();

        $this->assertPassed(AppDebugHideAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_non_empty_debug_hide()
    {
        $this->app->config->set('app.env', 'staging');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('app.debug_hide', ['_ENV' => ['APP_KEY']]);

        $this->runLaraBeacon();

        $this->assertPassed(AppDebugHideAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_non_empty_debug_blacklist()
    {
        $this->app->config->set('app.env', 'staging');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('app.debug_blacklist', ['_ENV' => ['APP_KEY']]);

        $this->runLaraBeacon();

        $this->assertPassed(AppDebugHideAnalyzer::class);
    }
}
