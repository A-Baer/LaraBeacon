<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\HSTSHeaderAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Route;

class HSTSHeaderAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(HSTSHeaderAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_http_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(HSTSHeaderAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_hsts_header_for_https_url()
    {
        $this->app->config->set('app.url', 'https://localhost');

        $this->app->make(HSTSHeaderAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, []),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertFailed(HSTSHeaderAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_hsts_header_for_secure_cookie_attributes()
    {
        $this->app->config->set('session.secure', true);

        $this->app->make(HSTSHeaderAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, []),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertFailed(HSTSHeaderAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_hsts_headers()
    {
        $this->app->config->set('session.secure', true);
        $this->app->config->set('app.url', 'https://localhost');

        $this->app->make(HSTSHeaderAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, ['Strict-Transport-Security' => 'max-age=86400']),
            ])]
        ));

        Route::get('/login', function () {
            //
        })->name('login');

        $this->runLaraBeacon();

        $this->assertPassed(HSTSHeaderAnalyzer::class);
    }
}
