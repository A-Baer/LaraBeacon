<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidOffsetAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\InvalidOffsetStub;

class InvalidOffsetAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidOffsetAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_offset()
    {
        $this->setBasePathFrom(InvalidOffsetStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidOffsetAnalyzer::class, $this->getClassStubPath(InvalidOffsetStub::class), 10);
        $this->assertFailedAt(InvalidOffsetAnalyzer::class, $this->getClassStubPath(InvalidOffsetStub::class), 13);
        $this->assertFailedAt(InvalidOffsetAnalyzer::class, $this->getClassStubPath(InvalidOffsetStub::class), 17);
        $this->assertFailedAt(InvalidOffsetAnalyzer::class, $this->getClassStubPath(InvalidOffsetStub::class), 25);
        $this->assertHasErrors(InvalidOffsetAnalyzer::class, 5);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_offset()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidOffsetAnalyzer::class);
    }
}
