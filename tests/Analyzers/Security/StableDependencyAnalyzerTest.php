<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\StableDependencyAnalyzer;
use BaerSoftware\LaraBeacon\Composer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;
use Mockery;

class StableDependencyAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(StableDependencyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_when_no_updates_are_available_within_current_constraints()
    {
        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('updateDryRun')->once()->with(['--prefer-stable'])
            ->andReturn('Nothing to modify in lock file');
        $this->app->instance(Composer::class, $composer);

        $this->runLaraBeacon();

        $this->assertPassed(StableDependencyAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_when_composer_proposes_an_update_within_current_constraints()
    {
        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('updateDryRun')->once()->with(['--prefer-stable'])
            ->andReturn('Upgrading example/package (1.0.0 => 1.1.0)');
        $this->app->instance(Composer::class, $composer);

        $this->runLaraBeacon();

        $this->assertFailed(StableDependencyAnalyzer::class);
        $this->assertErrorMessageContains(StableDependencyAnalyzer::class, 'newer dependency versions');
    }
}
