<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\EncryptedCookiesAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Route;

class EncryptedCookiesAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(EncryptedCookiesAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_stateless_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(EncryptedCookiesAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_encrypt_cookies_middleware()
    {
        $this->registerStatefulGlobalMiddleware();
        $this->registerProtectedRoute();

        $this->runLaraBeacon();

        $this->assertPassed(EncryptedCookiesAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_encrypt_cookies_middleware()
    {
        $this->registerStatefulGlobalMiddleware();
        $this->registerUnprotectedRoute();

        $this->runLaraBeacon();

        $this->assertFailed(EncryptedCookiesAnalyzer::class);
    }

    protected function registerProtectedRoute()
    {
        Route::middleware('web')->group(function () {
            Route::get('/test', function () {
                //
            });
        });
        $this->registerGroupMiddleware('web', EncryptCookies::class);
    }

    protected function registerUnprotectedRoute()
    {
        Route::get('/test', function () {
            //
        });
    }
}
