<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\FillableForeignKeyAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\FillableForeignKeyStub;

class FillableForeignKeyAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(FillableForeignKeyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_validation_sql_injection()
    {
        $this->setBasePathFrom(FillableForeignKeyStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(FillableForeignKeyAnalyzer::class, $this->getClassStubPath(FillableForeignKeyStub::class), 10);
        $this->assertHasErrors(FillableForeignKeyAnalyzer::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_injection_call()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(FillableForeignKeyAnalyzer::class);
    }
}
