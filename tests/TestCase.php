<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Inspection\Inspector;
use BaerSoftware\LaraBeacon\LaraBeacon;
use BaerSoftware\LaraBeacon\LaraBeaconServiceProvider;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        if ($this->app !== null) {
            $this->app->make(Inspector::class)->flush();
        }

        parent::tearDown();

        LaraBeacon::flush();
    }

    protected function getPackageProviders($app)
    {
        return [
            LaraBeaconServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->config->set('larabeacon.base_path', __DIR__.DIRECTORY_SEPARATOR.'Stubs');

        $app->config->set('larabeacon.analyzer_paths', [
            'BaerSoftware\\LaraBeacon\\Analyzers' => __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'src/Analyzers',
        ]);
    }
}
