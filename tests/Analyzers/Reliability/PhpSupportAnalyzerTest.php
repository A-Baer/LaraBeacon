<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\PhpSupportAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Support\Carbon;

class PhpSupportAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(PhpSupportAnalyzer::class, $app);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function securitySupportEnd(): Carbon
    {
        $release = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        return Carbon::parse(PhpSupportAnalyzer::SECURITY_SUPPORT_ENDS[$release]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_when_security_support_is_far_away()
    {
        Carbon::setTestNow($this->securitySupportEnd()->subDays(365));

        $this->runLaraBeacon();

        $this->assertPassed(PhpSupportAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_when_security_support_ends_soon()
    {
        Carbon::setTestNow($this->securitySupportEnd()->subDays(30));

        $this->runLaraBeacon();

        $this->assertFailed(PhpSupportAnalyzer::class);
        $this->assertErrorMessageContains(PhpSupportAnalyzer::class, 'receives security fixes only until');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_when_security_support_has_ended()
    {
        Carbon::setTestNow($this->securitySupportEnd()->addDays(2));

        $this->runLaraBeacon();

        $this->assertFailed(PhpSupportAnalyzer::class);
        $this->assertErrorMessageContains(PhpSupportAnalyzer::class, 'stopped receiving security fixes');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function respects_the_configured_warning_window()
    {
        Carbon::setTestNow($this->securitySupportEnd()->subDays(30));
        $this->app->config->set('larabeacon.support_warning_days', 7);

        $this->runLaraBeacon();

        $this->assertPassed(PhpSupportAnalyzer::class);
    }
}
