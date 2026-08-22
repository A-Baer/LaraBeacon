<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\CSRFAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

class CSRFAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CSRFAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_stateless_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(CSRFAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_global_csrf_middleware()
    {
        $this->clearMiddlewareGroups();
        $this->registerStatefulGlobalMiddleware();
        $this->registerGlobalCsrfMiddleware();

        $this->runLaraBeacon();

        $this->assertPassed(CSRFAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_web_group_middleware()
    {
        $this->clearMiddlewareGroups();
        $this->registerStatefulGlobalMiddleware();
        $this->registerGroupMiddleware('web', AppVerifyCsrfToken::class);

        $this->runLaraBeacon();

        $this->assertPassed(CSRFAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_post_routes_with_individual_csrf_protection()
    {
        $this->clearMiddlewareGroups();
        $this->registerStatefulGlobalMiddleware();
        $this->registerProtectedRoute();

        $this->runLaraBeacon();

        $this->assertPassed(CSRFAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_post_routes_without_protection()
    {
        $this->clearMiddlewareGroups();
        $this->registerStatefulGlobalMiddleware();
        $this->registerUnprotectedRoute();

        $this->runLaraBeacon();

        $this->assertFailed(CSRFAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_some_routes_without_protection()
    {
        $this->clearMiddlewareGroups();
        $this->registerStatefulGlobalMiddleware();
        $this->registerProtectedRoute();
        $this->registerUnprotectedRoute();

        $this->runLaraBeacon();

        $this->assertFailed(CSRFAnalyzer::class);
    }

    protected function registerGlobalCsrfMiddleware()
    {
        $this->app->make(Kernel::class)->pushMiddleware(AppVerifyCsrfToken::class);
    }

    protected function registerProtectedRoute()
    {
        Route::post('/i-am-secure', function () {
            return 'Go away CSRF attacker';
        })->middleware(AppVerifyCsrfToken::class);
    }

    protected function registerUnprotectedRoute()
    {
        Route::post('/i-am-insecure', function () {
            return 'Exploit me';
        });
    }
}

class AppVerifyCsrfToken extends VerifyCsrfToken
{
}
