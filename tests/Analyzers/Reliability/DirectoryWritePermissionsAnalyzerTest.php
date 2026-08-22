<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\DirectoryWritePermissionsAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;

class DirectoryWritePermissionsAnalyzerTest extends AnalyzerTestCase
{
    protected $files;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(DirectoryWritePermissionsAnalyzer::class, $app);

        $this->files = m::mock(Filesystem::class);

        $app->singleton(Filesystem::class, function () {
            return $this->files;
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_writable_directories()
    {
        $this->files->shouldReceive('isWritable')->andReturn(true);

        $this->runLaraBeacon();

        $this->assertPassed(DirectoryWritePermissionsAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unwritable_directories()
    {
        $this->files->shouldReceive('isWritable')->andReturn(false);

        $this->runLaraBeacon();

        $this->assertFailed(DirectoryWritePermissionsAnalyzer::class);
    }
}
