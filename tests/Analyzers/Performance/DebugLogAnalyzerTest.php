<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\DebugLogAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class DebugLogAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(DebugLogAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_critical_log_in_production()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('logging.default', 'slack');

        $this->runLaraBeacon();

        $this->assertPassed(DebugLogAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_debug_log_in_production()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('logging.default', 'single');

        $this->runLaraBeacon();

        $this->assertFailedAt(DebugLogAnalyzer::class, $this->getConfigStubPath('logging'), 47);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_stack_channel_debug_log_in_production()
    {
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('logging.default', 'stack');
        $this->app->config->set('logging.channels.stack.channels', ['single', 'daily', 'slack']);

        $this->runLaraBeacon();

        $this->assertFailedAt(DebugLogAnalyzer::class, $this->getConfigStubPath('logging'), 47);
        $this->assertFailedAt(DebugLogAnalyzer::class, $this->getConfigStubPath('logging'), 53);
        $this->assertHasErrors(DebugLogAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_local()
    {
        $this->app->config->set('app.env', 'local');
        $this->app->config->set('app.debug', true);

        $this->runLaraBeacon();

        $this->assertPassed(DebugLogAnalyzer::class);
    }
}
