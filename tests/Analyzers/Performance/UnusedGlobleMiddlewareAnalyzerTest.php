<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\UnusedGlobalMiddlewareAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;
use BaerSoftware\LaraBeacon\Tests\Middleware\DummyTrustProxiesL9;
use BaerSoftware\LaraBeacon\Tests\Middleware\UnusedTrustProxiesL9;
use Fruitcake\Cors\HandleCors;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Middleware\TrustHosts;

class UnusedGlobleMiddlewareAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(UnusedGlobalMiddlewareAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_global_middleware()
    {
        $this->clearMiddleware();

        $this->runLaraBeacon();

        $this->assertPassed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_trusted_hosts_without_trusted_proxies()
    {
        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(TrustHosts::class);

        $this->runLaraBeacon();

        $this->assertFailed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_wildcard_trusted_proxies()
    {
        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(DummyTrustProxiesL9::class);

        $this->runLaraBeacon();

        $this->assertPassed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unused_trusted_proxies()
    {
        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(UnusedTrustProxiesL9::class);

        $this->runLaraBeacon();

        $this->assertFailed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unused_cors()
    {
        $this->app->config->set('cors.paths', []);

        $kernel = $this->clearMiddleware();
        $kernel->pushMiddleware(HandleCors::class);

        $this->runLaraBeacon();

        $this->assertFailed(UnusedGlobalMiddlewareAnalyzer::class);
    }

    private function clearMiddleware()
    {
        $kernel = $this->app->make(Kernel::class);
        if (method_exists($kernel, 'setGlobalMiddleware')) {
            $kernel->setGlobalMiddleware([]);
        }

        return $kernel;
    }
}
