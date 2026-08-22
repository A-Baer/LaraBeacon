<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\UpToDateDependencyAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;

class UpToDateDependencyAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(UpToDateDependencyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_up_to_date_dependencies()
    {
        $this->runLaraBeacon();

        $this->assertPassed(UpToDateDependencyAnalyzer::class);
    }
}
