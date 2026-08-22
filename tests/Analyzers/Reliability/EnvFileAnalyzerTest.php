<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\EnvFileAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;

class EnvFileAnalyzerTest extends AnalyzerTestCase
{
    protected $files;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(EnvFileAnalyzer::class, $app);

        $this->files = m::mock(Filesystem::class);

        $app->singleton(Filesystem::class, function () {
            return $this->files;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_env_file()
    {
        $this->files->shouldReceive('exists')->with(base_path('.env'))->andReturn(true);

        $this->runLaraBeacon();

        $this->assertPassed(EnvFileAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_env_file()
    {
        $this->files->shouldReceive('exists')->with(base_path('.env'))->andReturn(false);

        $this->runLaraBeacon();

        $this->assertFailed(EnvFileAnalyzer::class);
    }
}
