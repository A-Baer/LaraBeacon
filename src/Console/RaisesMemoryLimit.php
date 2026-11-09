<?php

namespace BaerSoftware\LaraBeacon\Console;

trait RaisesMemoryLimit
{
    /**
     * Raise PHP's memory limit to the configured value, as Composer and PHPStan do, so that the default 128 MB
     * CLI limit does not abort the scan of a larger application. A higher or unlimited limit is never lowered.
     */
    protected function raiseMemoryLimit(string $limit): void
    {
        $required = static::memoryInBytes($limit);
        $current = static::memoryInBytes((string) ini_get('memory_limit'));

        if (! is_null($required) && ! is_null($current) && $current !== -1
            && ($required === -1 || $required > $current)) {
            ini_set('memory_limit', $limit);
        }
    }

    /**
     * Convert a php.ini memory value such as 128M, 1G or -1 to bytes.
     */
    protected static function memoryInBytes(string $value): ?int
    {
        $value = trim($value);

        if ($value === '-1') {
            return -1;
        }

        if (! preg_match('/^(\d+)\s*([kmg]?)$/i', $value, $matches)) {
            return null;
        }

        return (int) $matches[1] * match (strtolower($matches[2])) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };
    }
}
