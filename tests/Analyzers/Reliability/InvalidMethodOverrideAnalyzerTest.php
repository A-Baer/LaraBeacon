<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\InvalidMethodOverrideAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('< 8.0.0')]
class InvalidMethodOverrideAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(InvalidMethodOverrideAnalyzer::class, $app);

    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_invalid_method_overrides()
    {
        $this->app->config->set(
            'larabeacon.base_path',
            $path = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.
                'Stubs/InvalidMethodOverrideStub.php'
        );

        $this->runLaraBeacon();

        $this->assertFailedAt(InvalidMethodOverrideAnalyzer::class, $path, 14);
        $this->assertHasErrors(InvalidMethodOverrideAnalyzer::class, 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_invalid_overrides()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(InvalidMethodOverrideAnalyzer::class);
    }
}
