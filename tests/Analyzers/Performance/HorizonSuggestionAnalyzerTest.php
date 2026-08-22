<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\HorizonSuggestionAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class HorizonSuggestionAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(HorizonSuggestionAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_non_redis_queues()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(HorizonSuggestionAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_redis_queues_without_horizon()
    {
        $this->app->config->set('queue.default', 'redis');

        $this->runLaraBeacon();

        $this->assertFailed(HorizonSuggestionAnalyzer::class);
    }
}
