<?php

namespace BaerSoftware\LaraBeacon\Findings;

final class Identity
{
    // Lines are deliberately excluded: inserting a line must not create a new issue.
    public static function fingerprint(string $class, ?array $trace, int $occurrence = 0): string
    {
        return hash('sha256', json_encode([
            'v1', $class, $trace === null ? null : str_replace('\\', '/', $trace['path'] ?? ''),
            $trace['details'] ?? null, $trace['evidenceKey'] ?? null, $occurrence,
        ], JSON_THROW_ON_ERROR));
    }
}
