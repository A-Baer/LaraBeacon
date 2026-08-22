<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\LicenseAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;

class LicenseAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(LicenseAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function confirms_larabeacon_uses_dependencies_with_safe_licenses()
    {
        // Set GPL 2 as valid for LaraBeacon, since Larastan uses phpmyadmin/sql-parser.
        $this->app->config->set('larabeacon.license_whitelist', [...config('larabeacon.license_whitelist'), 'GPL-2.0-OR-LATER']);

        $this->runLaraBeacon();

        $this->assertPassed(LicenseAnalyzer::class);
    }
}
