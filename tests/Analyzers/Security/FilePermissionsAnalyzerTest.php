<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\FilePermissionsAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;

class FilePermissionsAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(FilePermissionsAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_max_permissions()
    {
        $this->app->config->set('larabeacon.allowed_permissions', [
            __DIR__ => '777',
        ]);

        $this->runLaraBeacon();

        $this->assertPassed(FilePermissionsAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_for_min_permissions()
    {
        $this->app->config->set('larabeacon.allowed_permissions', [
            __DIR__ => '000',
        ]);

        $this->runLaraBeacon();

        $this->assertFailed(FilePermissionsAnalyzer::class);
    }
}
