<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\UpToDateMigrationsAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Kernel;
use Illuminate\Support\Facades\Artisan;

class UpToDateMigrationsAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UpToDateMigrationsAnalyzer::class, $app);
    }

    /**
     * Resolve application Console Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Console\Kernel', Kernel::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_remaining_migrations()
    {
        Artisan::shouldReceive('call');
        Artisan::shouldReceive('output')->andReturn('Nothing to migrate.');

        $this->runLaraBeacon();

        $this->assertPassed(UpToDateMigrationsAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_pending_migrations()
    {
        Artisan::shouldReceive('call');
        Artisan::shouldReceive('output')->andReturn('create some table');

        $this->runLaraBeacon();

        $this->assertFailed(UpToDateMigrationsAnalyzer::class);
    }
}
