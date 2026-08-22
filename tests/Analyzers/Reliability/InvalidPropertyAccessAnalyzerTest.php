<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidPropertyAccessAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\InvalidAccessPropertiesStub;

class InvalidPropertyAccessAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidPropertyAccessAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_property_access()
    {
        $this->setBasePathFrom(InvalidAccessPropertiesStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidPropertyAccessAnalyzer::class, $this->getClassStubPath(InvalidAccessPropertiesStub::class), 16);
        $this->assertFailedAt(InvalidPropertyAccessAnalyzer::class, $this->getClassStubPath(InvalidAccessPropertiesStub::class), 17);
        $this->assertHasErrors(InvalidPropertyAccessAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_invalid_access()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidPropertyAccessAnalyzer::class);
    }
}
