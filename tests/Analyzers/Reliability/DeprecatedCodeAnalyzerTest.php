<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\DeprecatedCodeAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DeprecatedCodeStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;

class DeprecatedCodeAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(DeprecatedCodeAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_deprecated_code()
    {
        $this->setBasePathFrom(DeprecatedCodeStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 9);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 10);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 13);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 14);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 16);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 18);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 20);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 77);
        $this->assertFailedAt(DeprecatedCodeAnalyzer::class, $this->getClassStubPath(DeprecatedCodeStub::class), 90);
        $this->assertHasErrors(DeprecatedCodeAnalyzer::class, 9);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_deprecated_code()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(DeprecatedCodeAnalyzer::class);
    }
}
