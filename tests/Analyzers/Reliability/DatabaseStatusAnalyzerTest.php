<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\DatabaseStatusAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Mockery as m;

class DatabaseStatusAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(DatabaseStatusAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function checks_db_access()
    {
        $this->app->config->set('database.default', 'mysql');

        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getPdo');

        DB::shouldReceive('connection')->andReturn($connection);

        $this->runLaraBeacon();

        $this->assertPassed(DatabaseStatusAnalyzer::class);
    }
}
