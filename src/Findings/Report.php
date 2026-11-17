<?php

namespace BaerSoftware\LaraBeacon\Findings;

use BaerSoftware\LaraBeacon\Analyzers\Trace;

final class Report
{
    public static function build(array $results): array
    {
        $findings = [];
        $notApplicable = [];
        foreach ($results as $result) {
            if (($result['status'] ?? null) === 'skipped') {
                $notApplicable[] = [
                    'analyzer' => $result['class'], 'title' => $result['title'],
                    'reason' => $result['skipReason'] ?? null, 'documentation' => $result['docsUrl'] ?? null,
                ];

                continue;
            }
            if (! in_array($result['status'], ['failed', 'error'], true)) {
                continue;
            }
            $traces = array_map(fn ($trace) => $trace instanceof Trace ? [
                'path' => $trace->relativePath(), 'lineNumber' => $trace->lineNumber, 'details' => $trace->details, 'evidenceKey' => $trace->evidenceKey(),
            ] : $trace, $result['traces'] ?? []);
            $seen = [];
            foreach ($traces ?: [null] as $trace) {
                $key = Identity::fingerprint($result['class'], $trace);
                $ordinal = $seen[$key] ?? 0;
                $seen[$key] = $ordinal + 1;
                $findings[] = [
                    'fingerprint' => Identity::fingerprint($result['class'], $trace, $ordinal),
                    'analyzer' => $result['class'], 'title' => $result['title'],
                    'category' => $result['category'], 'severity' => $result['severity'],
                    'status' => $result['status'], 'reportable' => $result['reportable'] ?? true,
                    'message' => $result['error'] ?? $result['exception'] ?? '',
                    'location' => $trace, 'documentation' => $result['docsUrl'],
                    'guidance' => Guidance::forAnalyzer($result),
                ];
            }
        }

        return ['schema_version' => 1, 'findings' => $findings, 'not_applicable' => $notApplicable];
    }
}
