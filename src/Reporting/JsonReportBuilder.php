<?php

namespace BaerSoftware\LaraBeacon\Reporting;

use BaerSoftware\LaraBeacon\Analyzers\Trace;
use BaerSoftware\LaraBeacon\CommitHash;
use BaerSoftware\LaraBeacon\Composer;
use Illuminate\Container\Container;
use JsonSerializable;
use Throwable;

class JsonReportBuilder implements ReportBuilder
{
    /**
     * @param array $analyzerResults
     * @param array $analyzerStats
     * @param array $additionalData
     * @return array
     */
    public function buildReport(array $analyzerResults, array $analyzerStats, array $additionalData = [])
    {
        $metadata = array_merge($this->metadata(), $additionalData);

        return [
            'metadata' => $metadata,
            // An AI review needs the code around each finding; it is redacted before upload.
            'analyzer_results' => $this->normalizeAnalyzerResults($analyzerResults, ! empty($metadata['ai_triage'])),
            'analyzer_stats' => $analyzerStats,
        ];
    }

    /**
     * Normalize analyzer results before network transport and remove sensitive
     * diagnostic context unless the application explicitly opts in.
     *
     * @param array $analyzerResults
     * @return array
     */
    protected function normalizeAnalyzerResults(array $analyzerResults, bool $aiTriage = false)
    {
        $includeCodeSnippets = $aiTriage || (bool) config('larabeacon.cloud.include_code_snippets', false);
        $includeStackTraces = (bool) config('larabeacon.cloud.include_exception_stack_traces', false);

        return array_map(function ($result) use ($includeCodeSnippets, $includeStackTraces) {
            if (! is_array($result)) {
                return $result;
            }

            $result['guidance'] = \BaerSoftware\LaraBeacon\Findings\Guidance::forAnalyzer($result);

            if (! $includeStackTraces) {
                unset($result['stackTrace']);
            } elseif (isset($result['stackTrace']) && is_string($result['stackTrace'])) {
                $stackTrace = preg_split('/\R/u', trim($result['stackTrace']));

                $result['stackTrace'] = is_array($stackTrace)
                    ? array_values(array_filter($stackTrace, fn ($line) => $line !== ''))
                    : [];
            }

            if (isset($result['traces']) && is_array($result['traces'])) {
                $result['traces'] = array_map(
                    fn ($trace) => $this->normalizeTrace($trace, $includeCodeSnippets),
                    $result['traces'],
                );
            }

            return $result;
        }, $analyzerResults);
    }

    /**
     * @param mixed $trace
     * @return mixed
     */
    protected function normalizeTrace($trace, bool $includeCodeSnippet)
    {
        if ($trace instanceof Trace) {
            $normalized = [
                'path' => $trace->relativePath(),
                'lineNumber' => $trace->lineNumber,
                'details' => $trace->details,
                'evidenceKey' => $trace->evidenceKey(),
            ];

            if ($includeCodeSnippet) {
                $normalized['codeSnippet'] = SnippetRedactor::redact((string) $normalized['path'], $trace->codeSnippet());
            }

            return $normalized;
        }

        if ($trace instanceof JsonSerializable) {
            $trace = $trace->jsonSerialize();
        }

        if (is_array($trace) && ! $includeCodeSnippet) {
            unset($trace['codeSnippet']);
        } elseif (is_array($trace) && isset($trace['codeSnippet']) && is_array($trace['codeSnippet'])) {
            $trace['codeSnippet'] = SnippetRedactor::redact((string) ($trace['path'] ?? ''), $trace['codeSnippet']);
        }

        return $trace;
    }

    /**
     * Get the project metadata for the JSON report.
     *
     * @return array
     */
    public function metadata()
    {
        return [
            'app_name' => config('app.name'),
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'project_name' => $this->getProjectName(),
            'github_repo' => config('larabeacon.github_repo'),
            'commit_id' => CommitHash::get(),
            'trigger' => 'command',
        ];
    }

    /**
     * @return string|null
     */
    protected function getProjectName()
    {
        try {
            $composer = Container::getInstance()->make(Composer::class);

            $json = $composer->getJson();
        } catch (Throwable $throwable) {
            // Ignore any exceptions such as file not found.
            $json = [];
        }

        return $json['name'] ?? null;
    }
}
