<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Concerns\AnalyzesMiddleware;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

class CSRFAnalyzer extends SecurityAnalyzer
{
    use AnalyzesMiddleware;

    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application includes middleware to protect against CSRF attacks.';

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
     * The routes that are not protected from CSRF.
     *
     * @var \Illuminate\Support\Collection
     */
    public $unprotectedRoutes;

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
    }

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application is not adequately protected from CSRF attacks. There are {$this->unprotectedRoutes->count()} "
            ."unprotected routes, which include: {$this->formatUnprotectedRoutes()}. This can be very dangerous and you must "
            ."resolve this by adding CSRF middleware to your web routes.";
    }

    /**
     * Execute the analyzer.
     *
     * @return void
     * @throws \ReflectionException
     */
    public function handle()
    {
        if ($this->webMiddlewareGroupIsProtected()
            || $this->appIsGloballyProtected()
            || $this->routesAreIndividuallyProtected()) {
            return;
        }

        $this->markFailed();
    }

    /**
     * Determine whether to skip the analyzer.
     *
     * @return bool
     * @throws \ReflectionException
     */
    public function skip()
    {
        // Skip this analyzer if the app is stateless or does not use cookies (app does not need CSRF protection).
        return $this->appIsStateless() || ! $this->appUsesCookies();
    }

    /**
     * Determine if the "web" middleware group is protected from CSRF.
     *
     * @return bool
     */
    protected function webMiddlewareGroupIsProtected()
    {
        if (isset($this->kernel->getMiddlewareGroups()['web'])) {
            if (collect($this->kernel->getMiddlewareGroups()['web'])->contains(
                fn ($middleware) => $this->isCsrfMiddleware($middleware)
            )) {
                // Analysis passed as the web middleware group has CSRF middleware.
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the application is globally protected from CSRF.
     *
     * @return bool
     * @throws \ReflectionException
     */
    protected function appIsGloballyProtected()
    {
        if (collect($this->getGlobalMiddleware())->contains(
            fn ($middleware) => $this->isCsrfMiddleware($middleware)
        )) {
            // Analysis passed as CSRF middleware is global.
            return true;
        }

        return false;
    }

    /**
     * Determine if all routes are individually protected from CSRF.
     *
     * @return bool
     */
    protected function routesAreIndividuallyProtected()
    {
        $this->unprotectedRoutes = collect($this->router->getRoutes())->filter(function ($route) {
            // Exclude the whitelisted route methods that don't need protection
            return collect($route->methods())->contains(function ($method) {
                return ! in_array($method, ['HEAD', 'GET', 'OPTIONS']);
            });
        })->reject(function ($route) {
            // Laravel 12 registers signed local-disk upload routes. Their
            // signature validation protects them without CSRF middleware.
            return $this->isFrameworkStorageUploadRoute($route);
        })->filter(function ($route) {
            // Get the routes that don't apply CSRF middleware.
            return ! collect($this->getMiddleware($route))->contains(
                fn ($middleware) => $this->isCsrfMiddleware($middleware)
            );
        })->filter(function ($route) {
            // Exclude the routes that are API routes (do not need CSRF protection)
            return ! Str::is('api/*', ltrim($route->uri(), '/'));
        })->map(function ($route) {
            // Prettify unprotected routes to display in error message
            return '['.implode(',', $route->methods()).'] '.$route->uri();
        });

        return $this->unprotectedRoutes->count() == 0;
    }

    protected function isCsrfMiddleware(string $middleware): bool
    {
        return collect([
            PreventRequestForgery::class,
            ValidateCsrfToken::class,
            VerifyCsrfToken::class,
        ])->filter(fn (string $class) => class_exists($class))
            ->contains(fn (string $class) => $middleware === $class || is_subclass_of($middleware, $class));
    }

    /**
     * Determine whether a route is Laravel's signed local-disk upload route.
     */
    protected function isFrameworkStorageUploadRoute($route): bool
    {
        if ($route->methods() !== ['PUT']
            || ! preg_match('/^storage\.(.+)\.upload$/', $route->getName() ?? '', $matches)) {
            return false;
        }

        $disk = config('filesystems.disks.'.$matches[1], []);

        if (($disk['driver'] ?? null) !== 'local' || ($disk['serve'] ?? false) !== true) {
            return false;
        }

        $uri = isset($disk['url'])
            ? trim(parse_url($disk['url'], PHP_URL_PATH) ?? '', '/')
            : 'storage';

        return ltrim($route->uri(), '/') === $uri.'/{path}';
    }

    /**
     * @return string
     */
    protected function formatUnprotectedRoutes()
    {
        return $this->unprotectedRoutes->join(', ', ' and ');
    }
}
