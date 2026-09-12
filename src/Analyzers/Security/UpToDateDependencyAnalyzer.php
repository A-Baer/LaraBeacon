<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Composer;
use Illuminate\Support\Str;

class UpToDateDependencyAnalyzer extends SecurityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Installed dependencies match composer.lock.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_MINOR;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 1;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "The installed dependencies do not match composer.lock. Run Composer install to restore the locked "
            ."dependency set.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Composer $composer
     * @return void
     */
    public function handle(Composer $composer)
    {
        // First string match is for Composer 1 and the second one is for Composer 2.
        // First check is for all dependencies and second check is for production dependencies.
        if (! Str::contains(
            $composer->installDryRun(),
            ['Nothing to install or update', 'Nothing to install, update or remove']
        ) && ! Str::contains(
            $composer->installDryRun(['--no-dev']),
            ['Nothing to install or update', 'Nothing to install, update or remove']
        )) {
            $this->markFailed();
        }
    }
}
