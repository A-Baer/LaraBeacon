<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\CachePrefixAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\AppKeyAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\HSTSHeaderAnalyzer;
use BaerSoftware\LaraBeacon\Console\LaraBeaconCommand;
use BaerSoftware\LaraBeacon\LaraBeacon;
use Illuminate\Testing\PendingCommand;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class LaraBeaconCommandTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function raises_a_low_memory_limit_but_never_lowers_one()
    {
        $original = ini_get('memory_limit');

        try {
            ini_set('memory_limit', '128M');
            $this->artisan('larabeacon', ['--preflight' => true])->assertExitCode(0);
            $this->assertSame('1G', ini_get('memory_limit'));

            ini_set('memory_limit', '-1');
            $this->artisan('larabeacon', ['--preflight' => true])->assertExitCode(0);
            $this->assertSame('-1', ini_get('memory_limit'));

            ini_set('memory_limit', '2G');
            $this->artisan('larabeacon', ['--preflight' => true])->assertExitCode(0);
            $this->assertSame('2G', ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $original);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function failed_checks_are_grouped_by_severity()
    {
        $this->app->config->set('larabeacon.analyzers', [AppDebugAnalyzer::class, CachePrefixAnalyzer::class]);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', true);
        $this->app->config->set('cache.prefix', 'laravel_cache');

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput([]), $output = new BufferedOutput);
        $text = $output->fetch();

        $summary = substr($text, strpos($text, 'Findings by severity'));
        $this->assertStringContainsString('Critical (1)', $summary);
        $this->assertStringContainsString('https://larabeacon.com/checks/app-debug', $summary);
        $this->assertStringContainsString('https://larabeacon.com/checks/cache-prefix', $summary);
        $this->assertLessThan(strpos($summary, 'cache-prefix'), strpos($summary, 'app-debug'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function local_scans_without_a_profile_explain_where_to_run_production_checks()
    {
        $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
        $this->app->config->set('app.debug', false);

        foreach ([[[], true], [['--profile' => 'local'], false], [['--ci' => true], false]] as [$options, $hinted]) {
            $this->app->config->set('app.env', 'local');
            $command = new LaraBeaconCommand;
            $command->setLaravel($this->app);
            $command->run(new ArrayInput($options), $output = new BufferedOutput);
            $text = $output->fetch();

            $hinted
                ? $this->assertStringContainsString('APP_ENV is local', $text)
                : $this->assertStringNotContainsString('APP_ENV is local', $text);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function ai_reviews_require_a_cloud_upload()
    {
        $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $status = $command->run(new ArrayInput(['--ai' => true]), $output = new BufferedOutput);

        $this->assertSame(2, $status);
        $this->assertStringContainsString('--ai requires --report', $output->fetch());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function the_ai_triage_setting_requests_reviews_and_explains_uploads_without_code()
    {
        $this->app->config->set('larabeacon.analyzers', NoSeverityFailingAnalyzer::class);
        $this->app->config->set('larabeacon.cloud.ai_triage', true);

        $api = new class extends \BaerSoftware\LaraBeacon\Reporting\API
        {
            public array $reports = [];

            public function __construct() {}

            public function sendReport(array $report)
            {
                $this->reports[] = $report;

                return 'https://larabeacon.test/report';
            }
        };
        $this->app->instance(\BaerSoftware\LaraBeacon\Reporting\API::class, $api);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput(['--report' => true, '--branch' => 'release/1.0']), $output = new BufferedOutput);

        $this->assertTrue($api->reports[0]['metadata']['ai_triage']);
        $this->assertSame('release/1.0', $api->reports[0]['metadata']['branch']);
        $this->assertStringContainsString('nothing for the AI review to read', $output->fetch());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_reason_passed_to_mark_skipped_is_reported()
    {
        $this->app->config->set('larabeacon.analyzers', \BaerSoftware\LaraBeacon\Analyzers\Security\HashingStrengthAnalyzer::class);
        $this->app->config->set('hashing.driver', 'custom');

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput(['--format' => 'json']), $output = new BufferedOutput);
        $report = json_decode($output->fetch(), true);

        $this->assertStringContainsString('neither bcrypt nor Argon', $report['not_applicable'][0]['reason']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function failed_checks_without_a_known_severity_are_listed_under_other()
    {
        $this->app->config->set('larabeacon.analyzers', NoSeverityFailingAnalyzer::class);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput([]), $output = new BufferedOutput);
        $text = $output->fetch();

        $this->assertStringContainsString('Other (1)', substr($text, strpos($text, 'Findings by severity')));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function not_applicable_checks_explain_why()
    {
        $this->app->config->set('larabeacon.analyzers', HSTSHeaderAnalyzer::class);
        $this->app->config->set('app.url', 'http://localhost');
        $this->app->config->set('larabeacon.guest_url', null);
        $this->app->config->set('session.secure', false);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput([]), $output = new BufferedOutput);
        $this->assertStringContainsString('Not Applicable', $text = $output->fetch());
        $this->assertStringContainsString('not configured as HTTPS-only', $text);

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput(['--format' => 'json']), $output = new BufferedOutput);
        $report = json_decode($output->fetch(), true);
        $this->assertSame(HSTSHeaderAnalyzer::class, $report['not_applicable'][0]['analyzer']);
        $this->assertStringContainsString('not configured as HTTPS-only', $report['not_applicable'][0]['reason']);
        $this->assertSame('https://larabeacon.com/checks/hsts-header', $report['not_applicable'][0]['documentation']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function customers_with_pro_or_cloud_are_not_invited_again()
    {
        $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
        $this->app->config->set('app.env', 'production');
        $this->app->config->set('app.debug', false);
        $this->app->config->set('larabeacon.cloud.username', 'lbp_example');
        $this->app->config->set('larabeacon.cloud.api_token', 'lbct_example');

        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $command->run(new ArrayInput([]), $output = new BufferedOutput);
        $text = $output->fetch();

        $this->assertStringNotContainsString('https://larabeacon.com/register', $text);
        $this->assertStringNotContainsString('free Pro licence', $text);
        $this->assertStringContainsString('https://larabeacon.com/pro', $text);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invitations_only_appear_in_interactive_terminal_non_ci_output()
    {
        foreach (['interactive', 'ci', 'ci-profile', 'non-interactive', 'quiet', 'json', 'markdown', 'sarif', 'preflight'] as $mode) {
            $this->app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
            $this->app->config->set('app.env', 'production');
            $this->app->config->set('app.debug', false);

            $command = new LaraBeaconCommand;
            $command->setLaravel($this->app);
            $options = match ($mode) {
                'ci' => ['--ci' => true],
                'ci-profile' => ['--profile' => 'ci'],
                'json', 'markdown', 'sarif' => ['--format' => $mode],
                'preflight' => ['--preflight' => true],
                default => [],
            };
            $input = new ArrayInput($options);
            $input->setInteractive($mode !== 'non-interactive');
            $output = new BufferedOutput($mode === 'quiet' ? BufferedOutput::VERBOSITY_QUIET : BufferedOutput::VERBOSITY_NORMAL);

            $this->assertSame(0, $command->run($input, $output));
            $text = $output->fetch();

            if ($mode === 'interactive') {
                $this->assertStringContainsString('https://larabeacon.com/pro', $text);
                $this->assertStringContainsString('LaraBeacon Cloud: report history and team access.', $text);
                $this->assertStringContainsString('https://larabeacon.com/register', $text);
                $this->assertStringContainsString('Cloud uploads require --report and configured credentials.', $text);
                $this->assertStringContainsString('first five external projects', $text);
                $this->assertStringContainsString('anthony.baer@baer-software.com', $text);
            } else {
                $this->assertStringNotContainsString('https://larabeacon.com/register', $text, $mode);
                $this->assertStringNotContainsString('https://larabeacon.com/pro', $text, $mode);
                $this->assertStringNotContainsString('free Pro licence', $text, $mode);
            }
        }
    }

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
