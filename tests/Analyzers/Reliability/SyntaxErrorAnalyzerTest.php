<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\SyntaxErrorAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;

class SyntaxErrorAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(SyntaxErrorAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_syntax_errors()
    {
        $this->app->config->set('larabeacon.base_path', $this->getBaseStubPath());

        $this->runLaraBeacon();

        $errorPath = $this->getBaseStubPath().DIRECTORY_SEPARATOR.'SyntaxErrorStub.php';

        $this->assertFailedAt(SyntaxErrorAnalyzer::class, $errorPath, 5);
        $this->assertFailedAt(SyntaxErrorAnalyzer::class, $errorPath, 7);
        $this->assertHasErrors(SyntaxErrorAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_errors()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(SyntaxErrorAnalyzer::class);
    }
}
