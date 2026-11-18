<?php

namespace BaerSoftware\LaraBeacon\Reporting;

/**
 * Masks likely secrets in code excerpts before they leave the machine.
 * LaraBeacon Cloud repeats the same masking as a second line of defence.
 */
final class SnippetRedactor
{
    private const MASK = '[REDACTED]';

    private const KEY = '(?:password|passwd|pwd|secret|token|api[_-]?key|apikey|access[_-]?key|private[_-]?key|client[_-]?secret|auth[_-]?token|credential|signing[_-]?key|webhook[_-]?secret|salt)';

    /**
     * Redact an excerpt keyed by line number. Environment files are never sent.
     *
     * @param  array<int|string, mixed>  $lines
     * @return array<int|string, string>
     */
    public static function redact(string $path, array $lines): array
    {
        if (preg_match('#(^|/)\.env(\.[^/]*)?$#', str_replace('\\', '/', $path)) === 1) {
            return [];
        }

        $inBlock = false;
        $redacted = [];

        foreach ($lines as $number => $line) {
            if (! is_string($line)) {
                continue;
            }

            if (preg_match('/-----BEGIN [A-Z ]*PRIVATE KEY-----/', $line) === 1) {
                $inBlock = true;
            }

            if ($inBlock) {
                $inBlock = preg_match('/-----END [A-Z ]*PRIVATE KEY-----/', $line) !== 1;
                $redacted[$number] = self::MASK;

                continue;
            }

            $redacted[$number] = self::redactLine($line);
        }

        return $redacted;
    }

    public static function redactLine(string $line): string
    {
        $patterns = [
            // 'api_key' => 'value', "secret": "value", $password = 'value', SECRET=value
            '/((?:["\']?[\w.-]*'.self::KEY.'[\w.-]*["\']?)\s*(?:=>|=|:)\s*)(["\'])(?:(?!\2).){4,}\2/i' => '$1$2'.self::MASK.'$2',
            '/^(\s*(?:export\s+)?[A-Z0-9_]*'.self::KEY.'[A-Z0-9_]*\s*=\s*)(?!env\()(\S{4,})/i' => '$1'.self::MASK,
            '/\bAKIA[0-9A-Z]{16}\b/' => self::MASK,
            '/\b(?:sk|pk|rk)_(?:live|test)_[0-9A-Za-z]{10,}\b/' => self::MASK,
            '/\bsk-[A-Za-z0-9_-]{16,}\b/' => self::MASK,
            '/\b(?:ghp|gho|ghu|ghs|ghr)_[A-Za-z0-9]{20,}\b|\bgithub_pat_[A-Za-z0-9_]{20,}\b/' => self::MASK,
            '/\bxox[abposr]-[A-Za-z0-9-]{10,}\b/' => self::MASK,
            '/\beyJ[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\.[A-Za-z0-9_-]{8,}\b/' => self::MASK,
            '#(\b[a-z][a-z0-9+.-]*://[^\s:/@"\']+:)[^\s@/"\']+@#i' => '$1'.self::MASK.'@',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $line);
    }
}
