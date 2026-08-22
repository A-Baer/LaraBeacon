<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidMethodCallAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\InvalidMethodCallStub;

class InvalidMethodCallAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidMethodCallAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_method_calls()
    {
        $this->setBasePathFrom(InvalidMethodCallStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 9);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 10);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 30);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 31);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 33);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 40);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 41);
        $this->assertFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 42);
        $this->assertHasErrors(InvalidMethodCallAnalyzer::class, 8);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ignores_errors()
    {
        $this->setBasePathFrom(InvalidMethodCallStub::class);
        $this->app->config->set('larabeacon.ignore_errors', [InvalidMethodCallAnalyzer::class => [
            [
                'path' => $this->getClassStubPath(InvalidMethodCallStub::class),
                'details' => 'Call to an undefined method BaerSoftware\LaraBeacon\Tests\Stubs\InvalidMethodCallStub::protectedMethodFromChild().',
            ],
            [
                'path' => $this->getClassStubPath(InvalidMethodCallStub::class),
                'details' => '*undefined method BaerSoftware\LaraBeacon\Tests\Stubs\InvalidMethodCallStub::lorem*',
            ],
        ]]);

        $this->runLaraBeacon();

        $this->assertNotFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 9);
        $this->assertNotFailedAt(InvalidMethodCallAnalyzer::class, $this->getClassStubPath(InvalidMethodCallStub::class), 10);
        $this->assertHasErrors(InvalidMethodCallAnalyzer::class, 6);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_invalid_method_calls()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidMethodCallAnalyzer::class);
    }
}
