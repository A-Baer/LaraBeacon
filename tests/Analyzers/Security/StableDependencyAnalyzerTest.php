<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\StableDependencyAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;

class StableDependencyAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(StableDependencyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function confirms_larabeacon_has_stable_dependencies()
    {
        $this->runLaraBeacon();

        $this->assertPassed(StableDependencyAnalyzer::class);
    }
}
