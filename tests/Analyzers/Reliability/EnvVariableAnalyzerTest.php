<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\EnvVariableAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\EnvStub;

class EnvVariableAnalyzerTest extends AnalyzerTestCase
{
    protected $files;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(EnvVariableAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_env_variables()
    {
        $this->app->setBasePath(dirname($this->getClassStubPath(EnvStub::class)));

        $this->runLaraBeacon();

        $this->assertFailed(EnvVariableAnalyzer::class);
        $this->assertErrorMessageContains(EnvVariableAnalyzer::class, 'KEY_TWO');
        $this->assertErrorMessageDoesNotContain(EnvVariableAnalyzer::class, 'KEY_ONE');
        $this->assertErrorMessageDoesNotContain(EnvVariableAnalyzer::class, 'KEY_THREE');
        $this->assertErrorMessageDoesNotContain(EnvVariableAnalyzer::class, 'KEY_FOUR');
    }
}
