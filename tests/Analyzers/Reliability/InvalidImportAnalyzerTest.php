<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidImportAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\InvalidImportStub;

class InvalidImportAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidImportAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_return_statements()
    {
        $this->setBasePathFrom(InvalidImportStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidImportAnalyzer::class, $this->getClassStubPath(InvalidImportStub::class), 5);
        $this->assertHasErrors(InvalidImportAnalyzer::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_return_statements()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidImportAnalyzer::class);
    }
}
