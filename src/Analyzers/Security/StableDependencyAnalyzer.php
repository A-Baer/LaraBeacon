<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Composer;
use Illuminate\Support\Str;

class StableDependencyAnalyzer extends SecurityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'No dependency updates are available within the current Composer constraints.';

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
        return "A Composer update dry-run found newer dependency versions allowed by the current constraints. "
            ."Review the proposed updates and their changelogs before applying them.";
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Composer $composer
     * @return void
     */
    public function handle(Composer $composer)
    {
        if (Str::contains($composer->updateDryRun(['--prefer-stable']), ['Upgrading', 'Downgrading'])) {
            $this->markFailed();
        }
    }
}
