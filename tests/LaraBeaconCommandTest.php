<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\CachePrefixAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\AppKeyAnalyzer;
use BaerSoftware\LaraBeacon\Console\LaraBeaconCommand;
use BaerSoftware\LaraBeacon\LaraBeacon;
use Illuminate\Testing\PendingCommand;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class LaraBeaconCommandTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function command_exits_with_success_status_code_on_passing()
    {
        $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', false);

        $this->artisan('larabeacon')->assertExitCode(0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_exits_with_failure_status_code_on_failing()
    {
        $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);

        $this->artisan('larabeacon')->assertExitCode(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_exits_with_success_status_code_if_all_pass()
    {
        $this->app->config->set('larabeacon.analyzers', [AppDebugAnalyzer::class, CachePrefixAnalyzer::class]);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', false);
        $this->app->config->set('cache.prefix', 'larabeacon_cache');

        $this->artisan('larabeacon')->assertExitCode(0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_exits_with_failure_status_code_if_any_one_fails()
    {
        $this->app->config->set('larabeacon.analyzers', [AppDebugAnalyzer::class, CachePrefixAnalyzer::class]);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('cache.prefix', 'larabeacon_cache');

        $this->artisan('larabeacon')->assertExitCode(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function computes_percentage_properly()
    {
        if (! method_exists(PendingCommand::class, 'expectsTable')) {
            $this->markTestSkipped('Laravel 6.x does not provide table expectation assertions.');
        }

        $this->app->config->set('larabeacon.analyzers', [AppDebugAnalyzer::class, CachePrefixAnalyzer::class]);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('cache.prefix', 'larabeacon_cache');

        $rightAlign = (new TableStyle())->setPadType(STR_PAD_LEFT);

        $this->artisan('larabeacon')
            ->assertExitCode(1)
            ->expectsOutput("Report Card")
            ->expectsTable(
                ['Status', 'Reliability', 'Security', 'Total'],
                [
                    ['Passed', '1 (100%)', '0   (0%)', '1  (50%)'],
                    ['Failed', '0   (0%)', '1 (100%)', '1  (50%)'],
                    ['Not Applicable', '0   (0%)', '0   (0%)', '0   (0%)'],
                    ['Error', '0   (0%)', '0   (0%)', '0   (0%)'],
                ],
                'default',
                ['default', $rightAlign, $rightAlign, $rightAlign]
            );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_exits_with_success_status_code_if_not_reportable()
    {
        $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('larabeacon.dont_report', [AppDebugAnalyzer::class]);

        $this->artisan('larabeacon')->assertExitCode(0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_exits_with_failure_status_code_if_any_one_reportable_fails()
    {
        $this->app->config->set('larabeacon.analyzers', [AppDebugAnalyzer::class, CachePrefixAnalyzer::class]);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('cache.prefix', 'laravel_cache');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('larabeacon.dont_report', [AppDebugAnalyzer::class]);

        $this->artisan('larabeacon')->assertExitCode(1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_runs_in_ci_mode()
    {
        $this->app->config->set('larabeacon.analyzers', [
            AppDebugAnalyzer::class, AppKeyAnalyzer::class, CachePrefixAnalyzer::class,
        ]);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('cache.prefix', 'larabeacon_cache');
        $this->app->config->set('larabeacon.dont_report', [AppDebugAnalyzer::class]);

        // App debug fails but is not supposed to be reported, app key shouldn't run in CI and cache prefix succeeds.
        $this->artisan('larabeacon --ci')->assertExitCode(0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_catches_exceptions()
    {
        $this->app->config->set('larabeacon.analyzers', [
            FaultyAnalyzer::class,
        ]);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);

        LaraBeacon::$rethrowExceptions = false;

        $status = $command->run(new ArrayInput([]), $output = new BufferedOutput);

        $this->assertStringNotContainsString('FaultyAnalyzer', $output->fetch());
        $this->assertEquals(1, $status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function command_can_display_exceptions()
    {
        $this->app->config->set('larabeacon.analyzers', [
            FaultyAnalyzer::class,
        ]);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);

        LaraBeacon::$rethrowExceptions = false;

        $status = $command->run(new ArrayInput(['--show-exceptions' => true]), $output = new BufferedOutput);

        $this->assertStringContainsString('FaultyAnalyzer', $output->fetch());
        $this->assertEquals(1, $status);
    }
}
