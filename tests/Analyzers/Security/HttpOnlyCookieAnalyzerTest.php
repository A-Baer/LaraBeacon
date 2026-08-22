<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\HttpOnlyCookieAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;

class HttpOnlyCookieAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(HttpOnlyCookieAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_no_http_only()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->app->config->set('session.http_only', false);

        $this->runLaraBeacon();

        $this->assertFailedAt(HttpOnlyCookieAnalyzer::class, $this->getConfigStubPath('session'), 184);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_http_only()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->app->config->set('session.http_only', true);

        $this->runLaraBeacon();

        $this->assertPassed(HttpOnlyCookieAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_stateless_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(HttpOnlyCookieAnalyzer::class);
    }
}
