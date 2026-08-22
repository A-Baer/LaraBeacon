<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\UndefinedVariableAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\UndefinedVariableStub;

class UndefinedVariableAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UndefinedVariableAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_undefined_variables()
    {
        $this->setBasePathFrom(UndefinedVariableStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(UndefinedVariableAnalyzer::class, $this->getClassStubPath(UndefinedVariableStub::class), 9);
        $this->assertFailedAt(UndefinedVariableAnalyzer::class, $this->getClassStubPath(UndefinedVariableStub::class), 22);
        $this->assertHasErrors(UndefinedVariableAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_undefined_variables()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(UndefinedVariableAnalyzer::class);
    }
}
