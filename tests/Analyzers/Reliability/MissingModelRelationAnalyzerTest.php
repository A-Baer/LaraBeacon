<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\MissingModelRelationAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\MissingRelationStub;

class MissingModelRelationAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(MissingModelRelationAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_relations()
    {
        $this->setBasePathFrom(MissingRelationStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(MissingModelRelationAnalyzer::class, $this->getClassStubPath(MissingRelationStub::class), 19);
        $this->assertFailedAt(MissingModelRelationAnalyzer::class, $this->getClassStubPath(MissingRelationStub::class), 20);
        $this->assertHasErrors(MissingModelRelationAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_relations()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(MissingModelRelationAnalyzer::class);
    }
}
