<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Composer;

class LicenseAnalyzer extends SecurityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Dependency licenses are approved for this application.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MAJOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 60;

    /**
     * The blacklisted packages.
     *
     * @var \Illuminate\Support\Collection
     */
    public $blacklistedPackages;

    /**
     * All the packages used by the application.
     *
     * @var \Illuminate\Support\Collection
     */
    public $allPackages;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has {$this->blacklistedPackages->count()} package(s) whose licenses require project-specific "
            ."review. This is not proof of a licensing violation: approve compatible licenses in license_whitelist and "
            ."licensed proprietary packages in commercial_packages. Packages requiring review: "
            .$this->formatBlacklistedPackages();
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Composer $composer
     * @return void
     */
    public function handle(Composer $composer)
    {
        $whitelistedLicenses = array_map('strtoupper', config('larabeacon.license_whitelist', [
            'Apache-2.0', 'Apache2', 'BSD-2-Clause', 'BSD-3-Clause', 'LGPL-2.1-only', 'LGPL-2.1',
            'LGPL-2.1-or-later', 'LGPL-3.0', 'LGPL-3.0-only', 'LGPL-3.0-or-later', 'MIT', 'ISC',
            'CC0-1.0', 'Unlicense', 'WTFPL',
        ]));

        $commercialPackages = config('larabeacon.commercial_packages', []);

        $this->allPackages = $composer->getLicenses();
        $this->blacklistedPackages = $this->allPackages->map(function ($licenses) {
            return array_map('strtoupper', $licenses);
        })->filter(function ($licenses, $package) use ($whitelistedLicenses, $commercialPackages) {
            // Get all packages that have any licenses that are not whitelisted
            return empty(array_intersect($licenses, $whitelistedLicenses))
                && ! in_array($package, $commercialPackages);
        });

        if ($this->blacklistedPackages->count() > 0) {
            $this->markFailed();
        }
    }

    /**
     * @return string
     */
    public function formatBlacklistedPackages()
    {
        return $this->allPackages->intersectByKeys($this->blacklistedPackages)
            ->map(function ($licenses, $package) {
                return '['.$package.': '.implode(', ', $licenses).']';
            })->join(', ', ' and ');
    }
}
