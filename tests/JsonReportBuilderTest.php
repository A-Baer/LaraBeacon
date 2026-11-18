<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Trace;
use BaerSoftware\LaraBeacon\Reporting\JsonReportBuilder;
use PHPUnit\Framework\Attributes\Test;

class JsonReportBuilderTest extends TestCase
{
    #[Test]
    public function cloud_reports_exclude_source_and_stack_traces_by_default()
    {
        config()->set('larabeacon.cloud.include_code_snippets', false);
        config()->set('larabeacon.cloud.include_exception_stack_traces', false);

        $report = (new JsonReportBuilder())->buildReport([
            $this->analyzerResult(new Trace(__DIR__.'/Stubs/DummyStub.php', 3, 'Example finding')),
        ], []);

        $result = $report['analyzer_results'][0];
        $trace = $result['traces'][0];

        $this->assertArrayNotHasKey('stackTrace', $result);
        $this->assertArrayNotHasKey('codeSnippet', $trace);
        $this->assertSame(3, $trace['lineNumber']);
        $this->assertSame('Example finding', $trace['details']);
    }

    #[Test]
    public function cloud_reports_include_sensitive_diagnostics_only_after_explicit_opt_in()
    {
        config()->set('larabeacon.cloud.include_code_snippets', true);
        config()->set('larabeacon.cloud.include_exception_stack_traces', true);

        $report = (new JsonReportBuilder())->buildReport([
            $this->analyzerResult(new Trace(__DIR__.'/Stubs/DummyStub.php', 3, 'Example finding')),
        ], []);

        $result = $report['analyzer_results'][0];
        $trace = $result['traces'][0];

        $this->assertSame(['#0 frame one', '#1 frame two'], $result['stackTrace']);
        $this->assertNotEmpty($trace['codeSnippet']);
        $this->assertStringContainsString('DummyStub', implode("\n", $trace['codeSnippet']));
    }

    #[Test]
    public function pre_serialized_trace_payloads_are_filtered_too()
    {
        config()->set('larabeacon.cloud.include_code_snippets', false);

        $report = (new JsonReportBuilder())->buildReport([
            $this->analyzerResult([
                'path' => 'app/Example.php',
                'lineNumber' => 12,
                'details' => 'Example finding',
                'codeSnippet' => [12 => 'secret source'],
            ]),
        ], []);

        $this->assertArrayNotHasKey('codeSnippet', $report['analyzer_results'][0]['traces'][0]);
    }

    #[Test]
    public function ai_triage_uploads_include_redacted_snippets_without_the_general_opt_in()
    {
        config()->set('larabeacon.cloud.include_code_snippets', false);

        $report = (new JsonReportBuilder())->buildReport([
            $this->analyzerResult([
                'path' => 'app/Example.php',
                'lineNumber' => 12,
                'details' => 'Example finding',
                'codeSnippet' => [11 => "    'api_key' => 'live-value-1234',", 12 => '    return DB::select($sql);'],
            ]),
            $this->analyzerResult([
                'path' => '.env.production',
                'lineNumber' => 1,
                'details' => 'Example finding',
                'codeSnippet' => [1 => 'APP_KEY=base64:abc'],
            ]),
        ], [], ['ai_triage' => true]);

        $this->assertTrue($report['metadata']['ai_triage']);
        $this->assertSame(
            [11 => "    'api_key' => '[REDACTED]',", 12 => '    return DB::select($sql);'],
            $report['analyzer_results'][0]['traces'][0]['codeSnippet'],
        );
        $this->assertSame([], $report['analyzer_results'][1]['traces'][0]['codeSnippet']);
    }

    #[Test]
    public function environment_files_are_never_uploaded_from_trace_objects()
    {
        config()->set('larabeacon.cloud.include_code_snippets', true);
        $path = sys_get_temp_dir().'/larabeacon-'.bin2hex(random_bytes(4)).'/.env';
        mkdir(dirname($path));
        file_put_contents($path, "APP_KEY=base64:secret\nDB_PASSWORD=hunter22\n");

        try {
            $report = (new JsonReportBuilder())->buildReport([$this->analyzerResult(new Trace($path, 1, 'Example'))], []);
        } finally {
            unlink($path);
            rmdir(dirname($path));
        }

        $this->assertSame([], $report['analyzer_results'][0]['traces'][0]['codeSnippet']);
    }

    #[Test]
    public function guidance_examples_contain_real_line_breaks()
    {
        $example = \BaerSoftware\LaraBeacon\Findings\Guidance::forAnalyzer([
            'class' => 'BaerSoftware\\LaraBeacon\\Analyzers\\Security\\VulnerableDependencyAnalyzer',
            'status' => 'failed',
        ])['example'];

        $this->assertStringNotContainsString('\\n', $example);
        $this->assertSame(['composer audit --locked', 'composer update vendor/package --with-all-dependencies'], explode("\n", $example));
    }

    private function analyzerResult($trace): array
    {
        return [
            'title' => 'Example analyzer',
            'status' => 'failed',
            'traces' => [$trace],
            'stackTrace' => "#0 frame one\n#1 frame two",
        ];
    }
}
