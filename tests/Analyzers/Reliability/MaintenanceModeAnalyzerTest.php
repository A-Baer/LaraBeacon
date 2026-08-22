<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\MaintenanceModeAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class MaintenanceModeAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(MaintenanceModeAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function confirms_is_not_down()
    {
        $this->runLaraBeacon();

        $this->assertPassed(MaintenanceModeAnalyzer::class);
    }
}
