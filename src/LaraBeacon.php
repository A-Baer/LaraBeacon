<?php

namespace BaerSoftware\LaraBeacon;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use Throwable;

class LaraBeacon
{
    /**
     * The registered analyzer instances.
     *
     * @var array
     */
    public static $analyzerClasses = [];

    /**
     * The registered analyzer instances.
     *
     * @var array
     */
    public static $analyzers = [];

    /**
     * The registered analyzer categories
     *
     * @var array
     */
    public static $categories = [];

    /**
     * The callback to be executed after an analyzer is run.
     *
     * @var callable|null
     */
    public static $afterCallback = null;

    /**
     * The callback that filters the analyzers that should be run.
     *
     * @var callable|null
     */
    public static $filterCallback = null;

    /**
     * The callback to be executed before running all analyzers.
     *
     * @var callable|null
     */
    public static $beforeRunningCallback = null;

    /**
     * The paths of the files to run the analysis on.
     *
     * @var \Illuminate\Support\Collection
     */
    public static $filePaths;

    /**
     * Determine whether to re-throw analyzer exceptions.
     *
     * @var bool
     */
    public static $rethrowExceptions = false;

    /**
     * Register the LaraBeacon analyzers.
     *
     * @param array $analyzerClasses
     * @return void
     * @throws \ReflectionException
     */
    public static function register($analyzerClasses = [])
    {
        static::registerAnalyzers($analyzerClasses);
        static::$filePaths = static::getFilesToAnalyze();
    }

    /**
     * Run all the registered LaraBeacon analyzers.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\Throwable
     */
    public static function run($app)
    {
        static::$analyzers = [];

        foreach (static::$analyzerClasses as $analyzerClass) {
            $analyzer = $app->make($analyzerClass);

            static::$analyzers[] = $analyzer;
        }

        static::$analyzers = Arr::sort(static::$analyzers, function ($analyzer) {
            return $analyzer->category.get_class($analyzer);
        });

        static::callBeforeRunningCallback();

        foreach (static::$analyzers as $analyzer) {
            try {
                $analyzer->run($app);
            } catch (Throwable $e) {
                $analyzer->recordException($e);
                if (static::$rethrowExceptions) {
                    throw $e;
                }
            }

            static::callAfterCallback($analyzer);
        }
    }

    /**
     * Determine if LaraBeacon Pro is installed.
     *
     * @return bool
     */
    public static function isPro()
    {
        return class_exists(\BaerSoftware\LaraBeaconPro\LaraBeaconProServiceProvider::class);
    }

    /**
     * Get the package-owned analyzer directory.
     */
    public static function analyzerPath(): string
    {
        return __DIR__.'/Analyzers';
    }

    /**
     * Call the after callback on the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Analyzers\Analyzer $analyzer
     * @return void
     */
    public static function callAfterCallback(Analyzer $analyzer)
    {
        if (! is_null(static::$afterCallback)) {
            call_user_func(static::$afterCallback, $analyzer->getInfo());
        }
    }

    /**
     * Register an after callback on the analyzer.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function using($callback)
    {
        static::$afterCallback = $callback;
    }

    /**
     * Determine whether the analyzer should run based on the filter callback.
     *
     * @param string $class
     * @return bool
     */
    public static function filter(string $class)
    {
        return is_null(static::$filterCallback) ? true : call_user_func(static::$filterCallback, $class);
    }

    /**
     * Register a filter callback on the analyzer.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function filterUsing($callback)
    {
        static::$filterCallback = $callback;
    }

    /**
     * Set the filter callback to filter analyzers that should be run in CI mode.
     *
     * @return void
     */
    public static function filterAnalyzersForCI()
    {
        static::filterUsing(function ($class) {
            if (! empty($ciAnalyzers = config('larabeacon.ci_mode_analyzers'))) {
                return in_array($class, $ciAnalyzers);
            }

            return $class::$runInCI && ! in_array($class, config('larabeacon.ci_mode_exclude_analyzers'));
        });
    }

    /**
     * Call the before running callback.
     *
     * @return void
     */
    public static function callBeforeRunningCallback()
    {
        if (! is_null(static::$beforeRunningCallback)) {
            call_user_func(static::$beforeRunningCallback);
        }
    }

    /**
     * Register a before running callback.
     *
     * @param  callable  $callback
     * @return void
     */
    public static function beforeRunning($callback)
    {
        static::$beforeRunningCallback = $callback;
    }

    /**
     * Flush all the registered LaraBeacon analyzers and analyzer classes.
     *
     * @return void
     */
    public static function flush()
    {
        static::$analyzers = [];

        static::$categories = [];

        static::$analyzerClasses = [];

        static::$afterCallback = null;

        static::$filterCallback = null;

        static::$beforeRunningCallback = null;
    }

    /**
     * Determine if a given analyzer class has been registered.
     *
     * @param string $class
     * @return bool
     */
    public static function hasAnalyzer(string $class)
    {
        return in_array($class, static::$analyzerClasses);
    }

    /**
     * Determine if a given category has been registered.
     *
     * @param string $category
     * @return bool
     */
    public static function hasCategory(string $category)
    {
        return in_array($category, static::$categories);
    }

    /**
     * Get a collection of the files to analyze.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getFilesToAnalyze()
    {
        $paths = collect(config('larabeacon.base_path', [
            app_path(),
            base_path('routes'),
            config_path(),
            base_path('bootstrap/app.php'),
            database_path('migrations'),
            database_path('seeders'),
        ]))->filter(function ($path) {
            return file_exists($path);
        });

        $files = $paths->flatMap(function ($path) {
            if (is_file($path)) {
                return [$path];
            }

            return iterator_to_array(
                (new Finder)->in($path)->exclude('vendor')->name('*.php')
                    ->notName('*.blade.php')->files(),
                false
            );
        });

        return collect($files)->map(function ($file) {
            return is_string($file) ? $file : $file->getRealPath();
        })->filter()->unique()->values();
    }

    /**
     * Get the paths of the analyzers.
     *
     * @return array
     */
    public static function getAnalyzerPaths()
    {
        return collect(config('larabeacon.analyzer_paths', [self::class.'\\Analyzers' => __DIR__.'/Analyzers']))
                ->filter(function ($dir) {
                    return file_exists($dir);
                })->toArray();
    }

    /**
     * Get the configured LaraBeacon analyzer classes.
     *
     * @return array
     */
    public static function getAnalyzerClasses()
    {
        if (! in_array('*', Arr::wrap(config('larabeacon.analyzers', '*')))) {
            return Arr::wrap(config('larabeacon.analyzers'));
        }

        $analyzerClasses = [];

        if (empty($paths = static::getAnalyzerPaths())) {
            return [];
        }

        collect($paths)->each(function ($path, $baseNamespace) use (&$analyzerClasses) {
            $files = is_dir($path) ? (new Finder)->in($path)->files() : Arr::wrap($path);

            foreach ($files as $fileInfo) {
                $analyzerClass = $baseNamespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after(
                        is_string($fileInfo) ? $fileInfo : $fileInfo->getRealPath(),
                        realpath($path)
                    )
                );

                $analyzerClasses[] = $analyzerClass;
            }
        });

        if (empty($exclusions = config('larabeacon.exclude_analyzers', []))) {
            return $analyzerClasses;
        }

        return collect($analyzerClasses)->filter(function ($analyzerClass) use ($exclusions) {
            return ! in_array($analyzerClass, $exclusions);
        })->toArray();
    }

    /**
     * Get the count of the number of analyzers registered.
     *
     * @return int
     */
    public static function totalAnalyzers()
    {
        return (count(static::$analyzers) > 0) ? count(static::$analyzers) : count(static::$analyzerClasses);
    }

    /**
     * Register the configured LaraBeacon analyzer classes.
     *
     * @param array $analyzerClasses
     * @return void
     * @throws \ReflectionException
     */
    protected static function registerAnalyzers($analyzerClasses = [])
    {
        $analyzerClasses = empty($analyzerClasses) ? static::getAnalyzerClasses() : $analyzerClasses;

        foreach ($analyzerClasses as $analyzerClass) {
            static::registerAnalyzer($analyzerClass);
        }
    }

    /**
     * Register a LaraBeacon analyzer class.
     *
     * @param string $class
     * @return void
     * @throws \ReflectionException
     */
    protected static function registerAnalyzer($class)
    {
        if (is_subclass_of($class, Analyzer::class) &&
                ! (new ReflectionClass($class))->isAbstract() &&
                ! static::hasAnalyzer($class) &&
                static::filter($class)) {
            static::$analyzerClasses[] = $class;

            static::registerCategory($class);
        }
    }

    /**
     * Register a LaraBeacon analyzer category.
     *
     * @param string $class
     * @return void
     */
    protected static function registerCategory($class)
    {
        $category = get_class_vars($class)['category'];

        if (! is_null($category) && ! self::hasCategory($category)) {
            static::$categories[] = $category;
            static::$categories = Arr::sort(static::$categories);
        }
    }
}
