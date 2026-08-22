<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidFunctionCallAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\InvalidFunctionCallStub;

class InvalidFunctionCallAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidFunctionCallAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_function_calls()
    {
        $this->setBasePathFrom(InvalidFunctionCallStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidFunctionCallAnalyzer::class, $this->getClassStubPath(InvalidFunctionCallStub::class), 9);
        $this->assertFailedAt(InvalidFunctionCallAnalyzer::class, $this->getClassStubPath(InvalidFunctionCallStub::class), 11);
        $this->assertHasErrors(InvalidFunctionCallAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_invalid_function_calls()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidFunctionCallAnalyzer::class);
    }
}
