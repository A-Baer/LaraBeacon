<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidReturnTypeAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\InvalidReturnTypeStub;

class InvalidReturnTypeAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidReturnTypeAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_offset()
    {
        $this->setBasePathFrom(InvalidReturnTypeStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidReturnTypeAnalyzer::class, $this->getClassStubPath(InvalidReturnTypeStub::class), 14);
        $this->assertFailedAt(InvalidReturnTypeAnalyzer::class, $this->getClassStubPath(InvalidReturnTypeStub::class), 28);
        $this->assertFailedAt(InvalidReturnTypeAnalyzer::class, $this->getClassStubPath(InvalidReturnTypeStub::class), 32);
        $this->assertFailedAt(InvalidReturnTypeAnalyzer::class, $this->getClassStubPath(InvalidReturnTypeStub::class), 44);
        $this->assertFailedAt(InvalidReturnTypeAnalyzer::class, $this->getClassStubPath(InvalidReturnTypeStub::class), 58);
        $this->assertFailedAt(InvalidReturnTypeAnalyzer::class, $this->getClassStubPath(InvalidReturnTypeStub::class), 62);
        $this->assertHasErrors(InvalidReturnTypeAnalyzer::class, 6);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_offset()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidReturnTypeAnalyzer::class);
    }
}
