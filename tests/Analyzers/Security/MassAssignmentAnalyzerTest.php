<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\MassAssignmentAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\MassAssignmentStub;

class MassAssignmentAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(MassAssignmentAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_mass_assignment_vulnerabilities()
    {
        $this->setBasePathFrom(MassAssignmentStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 26);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 33);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 38);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 43);

        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 14);
        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 21);

        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 48);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 49);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 50);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 56);
        $this->assertFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 57);

        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 62);
        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 63);
        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 64);
        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 70);
        $this->assertNotFailedAt(MassAssignmentAnalyzer::class, $this->getClassStubPath(MassAssignmentStub::class), 71);

        $this->assertHasErrors(MassAssignmentAnalyzer::class, 9);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_injection_call()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(MassAssignmentAnalyzer::class);
    }
}
