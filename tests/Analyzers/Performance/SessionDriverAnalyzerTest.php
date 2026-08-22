<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\SessionDriverAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

class SessionDriverAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(SessionDriverAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skipped_for_stateless_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(SessionDriverAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_null_session_driver()
    {
        $this->app->config->set('session.driver', 'null');

        $this->registerDummyRouteWithSessionMiddleware();

        $this->runLaraBeacon();

        $this->assertFailedAt(SessionDriverAnalyzer::class, $this->getConfigStubPath('session'), 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_array_session_driver()
    {
        $this->app->config->set('session.driver', 'array');

        $this->registerDummyRouteWithSessionMiddleware();

        $this->runLaraBeacon();

        $this->assertFailedAt(SessionDriverAnalyzer::class, $this->getConfigStubPath('session'), 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_file_session_driver_in_production()
    {
        $this->app->config->set('session.driver', 'file');
        $this->app->config->set('app.env', 'production');

        $this->registerDummyRouteWithSessionMiddleware();

        $this->runLaraBeacon();

        $this->assertFailedAt(SessionDriverAnalyzer::class, $this->getConfigStubPath('session'), 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_file_session_driver_in_local()
    {
        $this->app->config->set('session.driver', 'file');
        $this->app->config->set('app.env', 'local');

        $this->registerDummyRouteWithSessionMiddleware();

        $this->runLaraBeacon();

        $this->assertPassed(SessionDriverAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_cookie_session_driver_in_production()
    {
        $this->app->config->set('session.driver', 'cookie');
        $this->app->config->set('app.env', 'production');

        $this->registerDummyRouteWithSessionMiddleware();

        $this->runLaraBeacon();

        $this->assertFailedAt(SessionDriverAnalyzer::class, $this->getConfigStubPath('session'), 12);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_cookie_session_driver_in_local()
    {
        $this->app->config->set('session.driver', 'cookie');
        $this->app->config->set('app.env', 'local');

        $this->registerDummyRouteWithSessionMiddleware();

        $this->runLaraBeacon();

        $this->assertPassed(SessionDriverAnalyzer::class);
    }

    protected function registerDummyRouteWithSessionMiddleware()
    {
        Route::get('/dummy')->middleware(StartSession::class);
    }
}
