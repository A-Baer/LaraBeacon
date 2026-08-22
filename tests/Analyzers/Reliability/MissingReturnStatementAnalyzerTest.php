<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\MissingReturnStatementAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\MissingReturnStub;

class MissingReturnStatementAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(MissingReturnStatementAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_return_statements()
    {
        $this->setBasePathFrom(MissingReturnStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(MissingReturnStatementAnalyzer::class, $this->getClassStubPath(MissingReturnStub::class), 7);
        $this->assertFailedAt(MissingReturnStatementAnalyzer::class, $this->getClassStubPath(MissingReturnStub::class), 13);
        $this->assertHasErrors(MissingReturnStatementAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_return_statements()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(MissingReturnStatementAnalyzer::class);
    }
}
