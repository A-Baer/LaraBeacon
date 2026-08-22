<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\CollectionCallAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\CollectionStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;

class CollectionCallAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CollectionCallAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_suboptimal_collection_call()
    {
        $this->setBasePathFrom(CollectionStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(CollectionCallAnalyzer::class, $this->getClassStubPath(CollectionStub::class), 11);
        $this->assertFailedAt(CollectionCallAnalyzer::class, $this->getClassStubPath(CollectionStub::class), 16);
        $this->assertHasErrors(CollectionCallAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_collection_call()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(CollectionCallAnalyzer::class);
    }
}
