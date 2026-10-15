<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\FrameworkSupportAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Support\Carbon;

class FrameworkSupportAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(FrameworkSupportAnalyzer::class, $app);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function securitySupportEnd(): Carbon
    {
        $major = (int) explode('.', $this->app->version())[0];

        return Carbon::parse(FrameworkSupportAnalyzer::SECURITY_SUPPORT_ENDS[$major]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_when_security_support_is_far_away()
    {
        Carbon::setTestNow($this->securitySupportEnd()->subDays(365));

        $this->runLaraBeacon();

        $this->assertPassed(FrameworkSupportAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_when_security_support_ends_soon()
    {
        Carbon::setTestNow($this->securitySupportEnd()->subDays(30));

        $this->runLaraBeacon();

        $this->assertFailed(FrameworkSupportAnalyzer::class);
        $this->assertErrorMessageContains(FrameworkSupportAnalyzer::class, 'receives security fixes only until');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_when_security_support_has_ended()
    {
        Carbon::setTestNow($this->securitySupportEnd()->addDays(2));

        $this->runLaraBeacon();

        $this->assertFailed(FrameworkSupportAnalyzer::class);
        $this->assertErrorMessageContains(FrameworkSupportAnalyzer::class, 'stopped receiving security fixes');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function respects_the_configured_warning_window()
    {
        Carbon::setTestNow($this->securitySupportEnd()->subDays(30));
        $this->app->config->set('larabeacon.support_warning_days', 7);

        $this->runLaraBeacon();

        $this->assertPassed(FrameworkSupportAnalyzer::class);
    }
}
