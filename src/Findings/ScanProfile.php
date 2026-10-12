<?php

namespace BaerSoftware\LaraBeacon\Findings;

use BaerSoftware\LaraBeacon\LaraBeacon;
use InvalidArgumentException;

final class ScanProfile
{
    public static function apply(?string $profile, bool $ci): void
    {
        if ($profile !== null && ! in_array($profile, ['local', 'ci', 'production'], true)) {
            throw new InvalidArgumentException('Profile must be local, ci or production.');
        }
        if ($ci && $profile !== null && $profile !== 'ci') {
            throw new InvalidArgumentException('--ci cannot be combined with another profile.');
        }
        if ($profile === 'production' && ! in_array(config('app.env'), ['production', 'prod', 'live', 'staging'], true)) {
            throw new InvalidArgumentException('The production profile requires an actual production-like environment (production, prod, live or staging). It does not change APP_ENV.');
        }
        if ($ci || in_array($profile, ['local', 'ci'], true)) {
            LaraBeacon::filterAnalyzersForCI();
        }
        if ($profile === 'local') {
            config()->set('larabeacon.skip_env_specific', true);
        }
    }

    public static function preflight(?string $profile): array
    {
        return [
            'profile' => $profile ?? 'configured',
            'environment' => config('app.env'),
            'php' => PHP_VERSION,
            'composer_lock_present' => is_file(base_path('composer.lock')),
            'configuration_cached' => app()->configurationIsCached(),
            'runtime_services_checked' => false,
            'note' => 'Local and CI profiles select CI-safe checks. Production scans may contact configured services. Missing prerequisites are execution errors, never a passing result. A successful scan covers only executed checks.',
        ];
    }
}
