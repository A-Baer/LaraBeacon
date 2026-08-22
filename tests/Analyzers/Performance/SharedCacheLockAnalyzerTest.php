<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\SharedCacheLockAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Stubs\DummyStub;
use BaerSoftware\LaraBeacon\Tests\Stubs\SharedCacheLockStub;

class SharedCacheLockAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(SharedCacheLockAnalyzer::class, $app);

        $app->config->set('cache.default', 'redis');
        $app->config->set('cache.stores.redis.lock_connection', null);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_cache_lock_method()
    {
        $this->setBasePathFrom(SharedCacheLockStub::class);

        $this->runLaraBeacon();

        $this->assertFailedAt(SharedCacheLockAnalyzer::class, $this->getClassStubPath(SharedCacheLockStub::class), 11);
        $this->assertFailedAt(SharedCacheLockAnalyzer::class, $this->getClassStubPath(SharedCacheLockStub::class), 16);
        $this->assertHasErrors(SharedCacheLockAnalyzer::class, 2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_separate_lock_connection()
    {
        $this->app->config->set('cache.stores.redis.lock_connection', 'default');
        $this->setBasePathFrom(SharedCacheLockStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(SharedCacheLockAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_no_cache_lock_method()
    {
        $this->setBasePathFrom(DummyStub::class);

        $this->runLaraBeacon();

        $this->assertPassed(SharedCacheLockAnalyzer::class);
    }
}
