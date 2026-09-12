<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\AnalyzesHeaders;
use BaerSoftware\LaraBeacon\Analyzers\Concerns\AnalyzesMiddleware;
use BaerSoftware\LaraBeacon\Analyzers\Concerns\DetectsHttps;
use GuzzleHttp\Client;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;

class HSTSHeaderAnalyzer extends SecurityAnalyzer
{
    use AnalyzesMiddleware;
    use AnalyzesHeaders;
    use DetectsHttps;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'The configured HTTPS target sends the HSTS header.';

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
    public $timeToFix = 5;

    /**
     * Determine whether the analyzer should be run in CI mode.
     *
     * @var bool
     */
    public static $runInCI = false;

    /**
     * Create a new analyzer instance.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @param  \Illuminate\Contracts\Http\Kernel  $kernel
     * @return void
     */
    public function __construct(Router $router, Kernel $kernel)
    {
        $this->router = $router;
        $this->kernel = $kernel;
        $this->client = new Client();
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "The configured HTTPS target does not include a HSTS (Strict-Transport-Security) response header that "
            ."tells browsers it should only be accessed using HTTPS. This may expose your "
            ."application to man-in-the-middle attacks.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     */
    public function handle()
    {
        if (! $this->headerExistsOnUrl($this->findLoginRoute(), 'Strict-Transport-Security')) {
            $this->markFailed();
        }
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     */
    public function skip()
    {
        // Skip this analyzer if the app is not an HTTPS only application.
        return ! $this->appIsHttpsOnly();
    }
}
