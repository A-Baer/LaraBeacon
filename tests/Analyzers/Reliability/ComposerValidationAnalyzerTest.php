<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\ComposerValidationAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;

class ComposerValidationAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(ComposerValidationAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function confirms_passes_for_larabeacon()
    {
        $this->runLaraBeacon();

        $this->assertPassed(ComposerValidationAnalyzer::class);
    }
}
