<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\AppKeyAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Support\Str;

class AppKeyAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(AppKeyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_no_app_key()
    {
        $this->app->config->set('app.key', null);

        $this->runLaraBeacon();

        $this->assertFailedAt(AppKeyAnalyzer::class, $this->getConfigStubPath('app'), 122);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_incompatible_key_and_cipher()
    {
        $this->app->config->set('app.key', 'blahblah');

        $this->runLaraBeacon();

        $this->assertFailedAt(AppKeyAnalyzer::class, $this->getConfigStubPath('app'), 122);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_proper_app_key_and_cipher()
    {
        $this->app->config->set('app.key', Str::random(32));
        $this->app->config->set('app.cipher', 'AES-256-CBC');

        $this->runLaraBeacon();

        $this->assertPassed(AppKeyAnalyzer::class);
    }
}
