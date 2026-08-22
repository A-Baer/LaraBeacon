<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\UnguardedModelsAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\UnguardedModelStub;

class UnguardedModelTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UnguardedModelsAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unguarded_models()
    {
        $this->setBasePathFrom(UnguardedModelStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(UnguardedModelsAnalyzer::class, $this->getClassStubPath(UnguardedModelStub::class), 11);
        $this->assertFailedAt(UnguardedModelsAnalyzer::class, $this->getClassStubPath(UnguardedModelStub::class), 16);
        $this->assertHasErrors(UnguardedModelsAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_no_unguarded_models()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(UnguardedModelsAnalyzer::class);
    }
}
