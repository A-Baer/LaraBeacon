<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\CacheHeaderAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Filesystem\Filesystem;

class CacheHeaderAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CacheHeaderAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_without_mix_manifest()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(CacheHeaderAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_missing_cache_headers()
    {
        $this->app->config->set('app.env', 'production');

        (new Filesystem())->copy(
            $this->getBaseStubPath().DIRECTORY_SEPARATOR.'mix'.DIRECTORY_SEPARATOR.'mix-manifest-versioned.json',
            public_path('mix-manifest.json')
        );

        $this->app->make(CacheHeaderAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, []),
                new Response(200, []),
                new Response(200, []),
                new Response(200, []),
            ])]
        ));

        $this->runLaraBeacon();

        $this->assertFailed(CacheHeaderAnalyzer::class);
        $this->assertErrorMessageContains(CacheHeaderAnalyzer::class, 'app.js');
        $this->assertErrorMessageContains(CacheHeaderAnalyzer::class, 'app.css');

        (new Filesystem())->delete(public_path('mix-manifest.json'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_cache_headers()
    {
        $this->app->config->set('app.env', 'production');

        (new Filesystem())->copy(
            $this->getBaseStubPath().DIRECTORY_SEPARATOR.'mix'.DIRECTORY_SEPARATOR.'mix-manifest-versioned.json',
            public_path('mix-manifest.json')
        );

        $this->app->make(CacheHeaderAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, ['Cache-Control' => 'max-age=86400']),
                new Response(200, ['Cache-Control' => 'max-age=86400']),
            ])]
        ));

        $this->runLaraBeacon();

        $this->assertPassed(CacheHeaderAnalyzer::class);

        (new Filesystem())->delete(public_path('mix-manifest.json'));
    }
}
