<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\HashingStrengthAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class HashingStrengthAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(HashingStrengthAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_secure_bcrypt()
    {
        $this->app->config->set('hashing.driver', 'bcrypt');
        $this->app->config->set('hashing.bcrypt.rounds', 12);

        $this->runLaraBeacon();

        $this->assertPassed(HashingStrengthAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_for_insecure_bcrypt()
    {
        $this->app->config->set('hashing.driver', 'bcrypt');
        $this->app->config->set('hashing.bcrypt.rounds', 10);

        $this->runLaraBeacon();

        $this->assertFailed(HashingStrengthAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_for_secure_argon()
    {
        $this->app->config->set('hashing.driver', 'argon');
        $this->app->config->set('hashing.argon.memory', 65536);
        $this->app->config->set('hashing.argon.threads', 1);
        $this->app->config->set('hashing.argon.time', 4);

        $this->runLaraBeacon();

        $this->assertPassed(HashingStrengthAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function fails_for_insecure_argon()
    {
        $this->app->config->set('hashing.driver', 'argon');
        $this->app->config->set('hashing.argon.memory', 1024);

        $this->runLaraBeacon();

        $this->assertFailed(HashingStrengthAnalyzer::class);
    }
}
