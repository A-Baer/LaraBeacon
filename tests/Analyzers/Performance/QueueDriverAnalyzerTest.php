<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\QueueDriverAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class QueueDriverAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(QueueDriverAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_null_queue_driver()
    {
        $this->app->config->set('queue.default', 'null');

        $this->runLaraBeacon();

        $this->assertFailedAt(QueueDriverAnalyzer::class, $this->getConfigStubPath('queue'), 16);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_sync_queue_driver()
    {
        $this->app->config->set('queue.default', 'sync');

        $this->runLaraBeacon();

        $this->assertFailedAt(QueueDriverAnalyzer::class, $this->getConfigStubPath('queue'), 16);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_database_queue_driver_in_non_local_env()
    {
        $this->app->config->set('queue.default', 'database');
        $this->app->config->set('app.env', 'production');

        $this->runLaraBeacon();

        $this->assertFailedAt(QueueDriverAnalyzer::class, $this->getConfigStubPath('queue'), 16);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_database_queue_driver_in_local_env()
    {
        $this->app->config->set('queue.default', 'database');
        $this->app->config->set('app.env', 'local');

        $this->runLaraBeacon();

        $this->assertPassed(QueueDriverAnalyzer::class);
    }
}
