<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\EnvAccessAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class EnvAccessAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(EnvAccessAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_publicly_accessible_env_file()
    {
        $this->app->make(EnvAccessAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(200, [], file_get_contents($this->getBaseStubPath().DIRECTORY_SEPARATOR.'.env.local')),
            ])]
        ));

        $this->runLaraBeacon();

        $this->assertFailed(EnvAccessAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_safe_setup()
    {
        $this->app->make(EnvAccessAnalyzer::class)->setClient(new Client(
            ['handler' => new MockHandler([
                new Response(404),
            ])]
        ));

        $this->runLaraBeacon();

        $this->assertPassed(EnvAccessAnalyzer::class);
    }
}
