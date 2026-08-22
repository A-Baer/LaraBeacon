<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\AutoloaderOptimizationAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class AutoloaderOptimizationAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(AutoloaderOptimizationAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_in_local()
    {
        $this->app->config->set('app.env', 'local');

        $this->runLaraBeacon();

        $this->assertSkipped(AutoloaderOptimizationAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_non_optimized_autoloader()
    {
        $this->app->config->set('app.env', 'production');

        // The active Composer autoloader must be found even if the Laravel base
        // path does not contain a conventional vendor directory.
        $this->app->setBasePath(sys_get_temp_dir().'/larabeacon-custom-vendor-app');

        $this->runLaraBeacon();

        $this->assertFailed(AutoloaderOptimizationAnalyzer::class);
    }
}
