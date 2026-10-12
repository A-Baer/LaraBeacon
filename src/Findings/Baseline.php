<?php

namespace BaerSoftware\LaraBeacon\Findings;

use InvalidArgumentException;

final class Baseline
{
    public static function load(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('Baseline is missing or unreadable: '.$path);
        }
        $data = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data) || ($data['schema_version'] ?? null) !== 1 || ! is_array($data['entries'] ?? null)) {
            throw new InvalidArgumentException('Unsupported baseline schema.');
        }
        foreach ($data['entries'] as $entry) {
            if (! is_array($entry) || ! is_string($entry['fingerprint'] ?? null) || ! preg_match('/^[a-f0-9]{64}$/', $entry['fingerprint']) || ! is_string($entry['reason'] ?? null) || trim($entry['reason']) === '') {
                throw new InvalidArgumentException('Each baseline entry requires a fingerprint and a reason.');
            }
            if (isset($entry['expires_at']) && ! is_string($entry['expires_at'])) {
                throw new InvalidArgumentException('Expiry must be a string date or null.');
            }
            self::validateExpiry($entry['expires_at'] ?? null);
        }

        return $data['entries'];
    }

    public static function validateExpiry(?string $date): void
    {
        if ($date !== null && (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || date('Y-m-d', strtotime($date)) !== $date)) {
            throw new InvalidArgumentException('Expiry must be a valid YYYY-MM-DD date.');
        }
    }

    public static function annotate(array $report, array $entries): array
    {
        $indexed = array_column($entries, null, 'fingerprint');
        foreach ($report['findings'] as &$finding) {
            $entry = $indexed[$finding['fingerprint']] ?? null;
            $finding['accepted'] = $finding['status'] !== 'error' && $entry !== null
                && (($entry['expires_at'] ?? null) === null || $entry['expires_at'] >= date('Y-m-d'));
            $finding['acceptance_reason'] = $finding['accepted'] ? $entry['reason'] : null;
        }
        unset($finding);
        $present = array_column($report['findings'], 'fingerprint');
        $report['stale_baseline_entries'] = array_values(array_diff(array_column($entries, 'fingerprint'), $present));

        return $report;
    }

    public static function blocks(array $report, string $threshold, bool $onlyNew): bool
    {
        $ranks = ['info' => 0, 'minor' => 1, 'major' => 2, 'critical' => 3];
        foreach ($report['findings'] as $finding) {
            if (! $finding['reportable']) {
                continue;
            }
            if ($finding['status'] === 'error') {
                return true;
            }
            if ($onlyNew && ($finding['accepted'] ?? false)) {
                continue;
            }
            if (($ranks[$finding['severity']] ?? 3) >= $ranks[$threshold]) {
                return true;
            }
        }

        return false;
    }
}
