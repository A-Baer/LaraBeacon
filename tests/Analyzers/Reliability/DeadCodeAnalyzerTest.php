<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\DeadCodeAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DeadCodeStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;

class DeadCodeAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(DeadCodeAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_dead_code()
    {
        $this->setBasePathFrom(DeadCodeStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(DeadCodeAnalyzer::class, $this->getClassStubPath(DeadCodeStub::class), 10);
        $this->assertFailedAt(DeadCodeAnalyzer::class, $this->getClassStubPath(DeadCodeStub::class), 16);
        $this->assertFailedAt(DeadCodeAnalyzer::class, $this->getClassStubPath(DeadCodeStub::class), 24);
        $this->assertFailedAt(DeadCodeAnalyzer::class, $this->getClassStubPath(DeadCodeStub::class), 35);
        $this->assertFailedAt(DeadCodeAnalyzer::class, $this->getClassStubPath(DeadCodeStub::class), 42);
        $this->assertFailedAt(DeadCodeAnalyzer::class, $this->getClassStubPath(DeadCodeStub::class), 47);
        $this->assertHasErrors(DeadCodeAnalyzer::class, 6);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_dead_code()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(DeadCodeAnalyzer::class);
    }
}
