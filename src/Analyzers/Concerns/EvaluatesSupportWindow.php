<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Concerns;

use Illuminate\Support\Carbon;

trait EvaluatesSupportWindow
{
    /**
     * The release that was evaluated.
     *
     * @var string|null
     */
    protected $evaluatedRelease = null;

    /**
     * The date on which security support ends for the evaluated release.
     *
     * @var \Illuminate\Support\Carbon|null
     */
    protected $securitySupportEndsAt = null;

    /**
     * Record a failure if security support ends within the configured window.
     *
     * @param  string  $release
     * @param  string|null  $endsAt
     * @return void
     */
    protected function evaluateSupportWindow(string $release, ?string $endsAt)
    {
        $this->evaluatedRelease = $release;

        if ($endsAt === null) {
            return;
        }

        $this->securitySupportEndsAt = Carbon::parse($endsAt)->endOfDay();
        $warningDays = (int) config('larabeacon.support_warning_days', 180);

        if (Carbon::now()->addDays(max($warningDays, 0))->greaterThanOrEqualTo($this->securitySupportEndsAt)) {
            $this->markFailed();
        }
    }

    /**
     * Describe the remaining security support period.
     *
     * @param  string  $subject
     * @return string
     */
    protected function supportWindowMessage(string $subject)
    {
        $date = $this->securitySupportEndsAt?->toDateString() ?? 'an unknown date';

        if ($this->securitySupportEndsAt?->isPast()) {
            return "{$subject} {$this->evaluatedRelease} stopped receiving security fixes on {$date}. "
                .'Plan the upgrade to a supported release before the next production release.';
        }

        $days = (int) Carbon::now()->diffInDays($this->securitySupportEndsAt);

        return "{$subject} {$this->evaluatedRelease} receives security fixes only until {$date} ({$days} days). "
            .'Schedule the upgrade now so it does not become an emergency.';
    }
}
