<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\MysqlSingleServerAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class MysqlSingleServerAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->config->set('app.env', 'production');
        $app->config->set('database.default', 'mysql');

        $this->setupEnvironmentFor(MysqlSingleServerAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_single_server_setup_without_sockets()
    {
        $this->runLaraBeacon();

        $this->assertFailedAt(MysqlSingleServerAnalyzer::class, $this->getConfigStubPath('database'), 54);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_single_server_setup_with_unix_sockets()
    {
        $this->app->config->set('database.connections.mysql.unix_socket', '/path/to/some/mysql.sock');

        $this->runLaraBeacon();

        $this->assertPassed(MysqlSingleServerAnalyzer::class);
    }
}
