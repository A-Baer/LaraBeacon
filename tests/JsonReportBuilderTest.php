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
