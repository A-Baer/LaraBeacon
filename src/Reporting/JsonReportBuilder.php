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
        return [
            'metadata' => array_merge($this->metadata(), $additionalData),
            'analyzer_results' => $this->normalizeAnalyzerResults($analyzerResults),
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
    protected function normalizeAnalyzerResults(array $analyzerResults)
    {
        $includeCodeSnippets = (bool) config('larabeacon.cloud.include_code_snippets', false);
        $includeStackTraces = (bool) config('larabeacon.cloud.include_exception_stack_traces', false);

        return array_map(function ($result) use ($includeCodeSnippets, $includeStackTraces) {
            if (! is_array($result)) {
                return $result;
            }

            if (! $includeStackTraces) {
                unset($result['stackTrace']);
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
            ];

            if ($includeCodeSnippet) {
                $normalized['codeSnippet'] = $trace->codeSnippet();
            }

            return $normalized;
        }

        if ($trace instanceof JsonSerializable) {
            $trace = $trace->jsonSerialize();
        }

        if (is_array($trace) && ! $includeCodeSnippet) {
            unset($trace['codeSnippet']);
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
