<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\DevDependencyAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;

class DevDependencyAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(DevDependencyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_dev_dependencies_in_production()
    {
        $this->app->config->set('app.env', 'production');

        $this->runLaraBeacon();

        $this->assertFailed(DevDependencyAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_dev_dependencies_in_local()
    {
        $this->app->config->set('app.env', 'local');

        $this->runLaraBeacon();

        $this->assertPassed(DevDependencyAnalyzer::class);
    }
}
