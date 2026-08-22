<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\XSSAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Route;

class XSSAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(XSSAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_stateless_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(XSSAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_local()
    {
        $this->app->config->set('app.env', 'local');

        $this->registerStatefulGlobalMiddleware();

        $this->runLaraBeacon();

        $this->assertSkipped(XSSAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_csp_header()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->app->make(XSSAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, []),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertFailed(XSSAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unsafe_csp_header()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->app->make(XSSAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, ['Content-Security-Policy' => "default-src 'self' 'unsafe-inline'"]),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertFailed(XSSAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_default_csp_header()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->app->make(XSSAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, ['Content-Security-Policy' => "default-src 'self'"]),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertPassed(XSSAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_script_csp_header()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->app->make(XSSAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, ['Content-Security-Policy' => "script-src 'self'"]),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertPassed(XSSAnalyzer::class);
    }
}
