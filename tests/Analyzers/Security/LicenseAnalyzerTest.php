<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\LicenseAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;
use Illuminate\Support\Collection;
use Mockery;

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function licensed_commercial_packages_can_be_approved_without_whitelisting_proprietary_as_a_license()
    {
        $composer = Mockery::mock(\BaerSoftware\LaraBeacon\Composer::class);
        $composer->shouldReceive('getLicenses')->once()->andReturn(new Collection([
            'vendor/commercial-package' => ['proprietary'],
        ]));
        $this->app->instance(\BaerSoftware\LaraBeacon\Composer::class, $composer);
        $this->app->config->set('larabeacon.commercial_packages', ['vendor/commercial-package']);

        $this->runLaraBeacon();

        $this->assertPassed(LicenseAnalyzer::class);
    }
}
