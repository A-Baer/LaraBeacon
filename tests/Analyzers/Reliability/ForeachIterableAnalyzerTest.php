<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\ForeachIterableAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\ForeachIterableStub;

class ForeachIterableAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(ForeachIterableAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_non_iterable_foreach()
    {
        $this->setBasePathFrom(ForeachIterableStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(ForeachIterableAnalyzer::class, $this->getClassStubPath(ForeachIterableStub::class), 10);
        $this->assertFailedAt(ForeachIterableAnalyzer::class, $this->getClassStubPath(ForeachIterableStub::class), 19);
        $this->assertHasErrors(ForeachIterableAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_foreach()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(ForeachIterableAnalyzer::class);
    }
}
