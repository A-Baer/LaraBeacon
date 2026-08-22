<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\EnvCallAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\EnvStub;

class EnvCallAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(EnvCallAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_env_function_call()
    {
        $this->setBasePathFrom(EnvStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(EnvCallAnalyzer::class, $this->getClassStubPath(EnvStub::class), 9);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ignores_errors()
    {
        $this->setBasePathFrom(EnvStub::class);
        $this->app->config->set('larabeacon.ignore_errors', [EnvCallAnalyzer::class => [
            [
                'path' => $this->getClassStubPath(EnvStub::class),
                'details' => 'Function env called.',
            ],
        ]]);

        $this->runLaraBeacon();

        $this->assertPassed(EnvCallAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_env_call()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(EnvCallAnalyzer::class);
    }
}
