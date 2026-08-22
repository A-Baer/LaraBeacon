<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\EnvExampleAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\EnvStub;

class EnvExampleAnalyzerTest extends AnalyzerTestCase
{
    protected $files;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(EnvExampleAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_env_variables()
    {
        $this->app->setBasePath(dirname($this->getClassStubPath(EnvStub::class)));

        $this->runLaraBeacon();

        $this->assertFailed(EnvExampleAnalyzer::class);
        $this->assertErrorMessageContains(EnvExampleAnalyzer::class, 'KEY_FOUR');
        $this->assertErrorMessageDoesNotContain(EnvExampleAnalyzer::class, 'KEY_ONE');
        $this->assertErrorMessageDoesNotContain(EnvExampleAnalyzer::class, 'KEY_TWO');
        $this->assertErrorMessageDoesNotContain(EnvExampleAnalyzer::class, 'KEY_THREE');
    }
}
