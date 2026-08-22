<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\UndefinedConstantAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\UndefinedConstantStub;

class UndefinedConstantAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UndefinedConstantAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_return_statements()
    {
        $this->setBasePathFrom(UndefinedConstantStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(UndefinedConstantAnalyzer::class, $this->getClassStubPath(UndefinedConstantStub::class), 9);
        $this->assertHasErrors(UndefinedConstantAnalyzer::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_return_statements()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(UndefinedConstantAnalyzer::class);
    }
}
