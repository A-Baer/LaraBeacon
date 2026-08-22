<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\UnsetAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\UnsetStub;

class UnsetAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UnsetAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_unset_statements()
    {
        $this->setBasePathFrom(UnsetStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(UnsetAnalyzer::class, $this->getClassStubPath(UnsetStub::class), 9);
        $this->assertFailedAt(UnsetAnalyzer::class, $this->getClassStubPath(UnsetStub::class), 10);
        $this->assertFailedAt(UnsetAnalyzer::class, $this->getClassStubPath(UnsetStub::class), 13);
        $this->assertFailedAt(UnsetAnalyzer::class, $this->getClassStubPath(UnsetStub::class), 16);
        $this->assertFailedAt(UnsetAnalyzer::class, $this->getClassStubPath(UnsetStub::class), 19);
        $this->assertHasErrors(UnsetAnalyzer::class, 5);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_unset_statements()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(UnsetAnalyzer::class);
    }
}
