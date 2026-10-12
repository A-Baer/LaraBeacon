<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Performance\EnvCallAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugAnalyzer;
use BaerSoftware\LaraBeacon\Console\LaraBeaconCommand;
use BaerSoftware\LaraBeacon\Findings\Baseline;
use BaerSoftware\LaraBeacon\Findings\Exporter;
use BaerSoftware\LaraBeacon\Findings\FixPreview;
use BaerSoftware\LaraBeacon\Findings\Identity;
use BaerSoftware\LaraBeacon\Findings\Report;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class RemediationWorkflowTest extends TestCase
{
    private function runCommand(array $arguments): array
    {
        $command = new LaraBeaconCommand;
        $command->setLaravel($this->app);
        $input = new ArrayInput($arguments);
        $input->setInteractive(false);
        $output = new BufferedOutput;
        $status = $command->run($input, $output);

        return [$status, $output->fetch()];
    }

    private function debugSetup(): void
    {
        config()->set('larabeacon.analyzers', [AppDebugAnalyzer::class]);
        config()->set('app.env', 'production');
        config()->set('app.debug', true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function json_stdout_is_clean_and_contains_remediation_without_source(): void
    {
        $this->debugSetup();
        [$status, $text] = $this->runCommand(['--format' => 'json']);
        $report = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $status);
        $this->assertSame('high', $report['findings'][0]['guidance']['confidence']);
        $this->assertArrayNotHasKey('codeSnippet', $report['findings'][0]['location'] ?? []);
        $this->assertStringContainsString('APP_DEBUG=false', Exporter::render($report, 'markdown'));
        $sarif = json_decode(Exporter::render($report, 'sarif'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('2.1.0', $sarif['version']);
        $this->assertSame($report['findings'][0]['fingerprint'], $sarif['runs'][0]['results'][0]['partialFingerprints']['larabeacon/v1']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function per_occurrence_identity_survives_line_shifts_and_distinguishes_findings(): void
    {
        $a = ['path' => 'app/Foo.php', 'lineNumber' => 10, 'details' => 'env(APP_NAME)'];
        $b = array_replace($a, ['lineNumber' => 30]);
        $this->assertSame(Identity::fingerprint('A', $a), Identity::fingerprint('A', $b));
        $this->assertNotSame(Identity::fingerprint('A', $a), Identity::fingerprint('A', $a, 1));
        $this->assertNotSame(Identity::fingerprint('A', $a), Identity::fingerprint('A', array_replace($a, ['details' => 'other'])));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function only_new_honors_expiry_and_never_suppresses_execution_errors(): void
    {
        $this->debugSetup();
        [, $json] = $this->runCommand(['--format' => 'json']);
        $report = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $entry = ['fingerprint' => $report['findings'][0]['fingerprint'], 'reason' => 'Known issue', 'expires_at' => date('Y-m-d')];
        $accepted = Baseline::annotate($report, [$entry]);
        $this->assertFalse(Baseline::blocks($accepted, 'critical', true));
        $entry['expires_at'] = '2000-01-01';
        $this->assertTrue(Baseline::blocks(Baseline::annotate($report, [$entry]), 'critical', true));
        $report['findings'][0]['status'] = 'error';
        $entry['expires_at'] = null;
        $this->assertTrue(Baseline::blocks(Baseline::annotate($report, [$entry]), 'critical', true));
        [$status] = $this->runCommand(['--only-new' => true]);
        $this->assertSame(2, $status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function threshold_and_profiles_validate_without_changing_environment(): void
    {
        config()->set('larabeacon.analyzers', [\BaerSoftware\LaraBeacon\Analyzers\Reliability\CachePrefixAnalyzer::class]);
        config()->set('cache.prefix', 'laravel_cache');
        config()->set('app.env', 'local');
        [$status] = $this->runCommand(['--fail-on' => 'critical']);
        $this->assertSame(0, $status);
        [$status, $json] = $this->runCommand(['--profile' => 'local', '--preflight' => true]);
        $this->assertSame(0, $status);
        $this->assertSame('local', json_decode($json, true)['environment']);
        $this->assertFalse(config('larabeacon.skip_env_specific'));
        [$status] = $this->runCommand(['--profile' => 'production']);
        $this->assertSame(2, $status);
        [$status] = $this->runCommand(['--ci' => true, '--profile' => 'local']);
        $this->assertSame(2, $status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function baseline_can_be_generated_used_and_pruned_without_editing_configuration(): void
    {
        $this->debugSetup();
        $file = tempnam(sys_get_temp_dir(), 'lb-baseline-');
        unlink($file);

        try {
            $this->artisan('larabeacon:baseline', ['--output' => $file, '--reason' => 'Scheduled remediation'])->assertExitCode(0);
            $this->assertNotEmpty(Baseline::load($file));
            [$status, $json] = $this->runCommand(['--format' => 'json', '--baseline' => $file, '--only-new' => true]);
            $this->assertSame(0, $status);
            $this->assertTrue(json_decode($json, true)['findings'][0]['accepted']);
            config()->set('app.debug', false);
            $this->artisan('larabeacon:baseline', ['--output' => $file, '--reason' => 'Scheduled remediation'])->assertExitCode(0);
            $this->assertSame([], Baseline::load($file));
        } finally {
            @unlink($file);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function previews_use_real_config_mappings_and_do_not_edit_files(): void
    {
        $directory = base_path('lb-preview-fixture');
        mkdir($directory);
        $config = $directory.'/app.php';
        $code = $directory.'/Service.php';
        file_put_contents($config, "<?php\nreturn ['debug' => true, 'name' => env('APP_NAME', 'Laravel')];\n");
        file_put_contents($code, "<?php\nreturn env('APP_NAME');\n");
        config()->set('larabeacon.config_path', $directory);
        $results = [];
        foreach ([[AppDebugAnalyzer::class, $config], [EnvCallAnalyzer::class, $code]] as [$class, $path]) {
            $result = (new $class)->getInfo();
            $result['status'] = 'failed';
            $result['traces'] = [new \BaerSoftware\LaraBeacon\Analyzers\Trace($path, 2)];
            $results[] = $result;
        }

        try {
            $original = file_get_contents($config);
            $previews = FixPreview::build(Report::build($results));
            $this->assertCount(2, $previews);
            $this->assertStringContainsString("'debug' => false", $previews[0]['diff']);
            $this->assertStringContainsString("config('app.name')", $previews[1]['diff']);
            $this->assertSame($original, file_get_contents($config));
            file_put_contents($code, "<?php\nreturn env('APP_NAME', 'different default');\n");
            $this->assertCount(1, FixPreview::build(Report::build($results)));
        } finally {
            unlink($config);
            unlink($code);
            rmdir($directory);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function markdown_retains_execution_diagnostics_without_a_location(): void
    {
        config()->set('larabeacon.analyzers', [FaultyAnalyzer::class]);
        \BaerSoftware\LaraBeacon\LaraBeacon::$rethrowExceptions = false;
        [$status, $text] = $this->runCommand(['--format' => 'markdown']);
        $this->assertSame(1, $status);
        $this->assertStringContainsString('Evidence:', $text);
        $this->assertStringContainsString('Test exception thrown by faulty analyzer', $text);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function malformed_baseline_field_types_are_configuration_errors(): void
    {
        $this->debugSetup();
        $file = tempnam(sys_get_temp_dir(), 'lb-invalid-');

        try {
            foreach ([['fingerprint' => []], ['expires_at' => []], ['reason' => []]] as $invalid) {
                $entry = array_replace(['fingerprint' => str_repeat('a', 64), 'reason' => 'Reviewed', 'expires_at' => null], $invalid);
                file_put_contents($file, json_encode(['schema_version' => 1, 'entries' => [$entry]], JSON_THROW_ON_ERROR));
                [$status] = $this->runCommand(['--baseline' => $file, '--only-new' => true]);
                $this->assertSame(2, $status);
                $this->artisan('larabeacon:baseline', ['--output' => $file, '--reason' => 'Reviewed'])->assertExitCode(2);
            }
        } finally {
            unlink($file);
        }
    }
}
