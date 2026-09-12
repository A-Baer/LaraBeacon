<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LaraBeacon Analyzer Classes
    |--------------------------------------------------------------------------
    |
    | The following array lists the "analyzer" classes that will be registered
    | with LaraBeacon. These analyzers run an analysis on the application via
    | various methods such as static analysis. Feel free to customize it.
    |
    */
    'analyzers' => ['*'],

    // If you wish to skip running some analyzers, list the classes in the array below.
    'exclude_analyzers' => [],

    // If you wish to skip running some analyzers in CI mode, list the classes below.
    'ci_mode_exclude_analyzers' => [],

    /*
    |--------------------------------------------------------------------------
    | LaraBeacon Analyzer Paths
    |--------------------------------------------------------------------------
    |
    | The following array lists the "analyzer" paths that will be searched
    | recursively to find analyzer classes. This option will only be used
    | if the analyzers option above is set to the asterisk wildcard. The
    | key is the base namespace to resolve the class name.
    |
    */
    'analyzer_paths' => [
        'BaerSoftware\\LaraBeacon\\Analyzers' => \BaerSoftware\LaraBeacon\LaraBeacon::analyzerPath(),
    ],

    /*
    |--------------------------------------------------------------------------
    | LaraBeacon Base Path
    |--------------------------------------------------------------------------
    |
    | The following array lists the files and directories that will be scanned
    | for application-specific code. Routes, configuration and bootstrap/app.php
    | are included because modern Laravel applications define important runtime
    | and security behavior there.
    |
    */
    'base_path' => [
        app_path(),
        base_path('routes'),
        config_path(),
        base_path('bootstrap/app.php'),
        database_path('migrations'),
        database_path('seeders'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Specific Analyzers
    |--------------------------------------------------------------------------
    |
    | There are some analyzers that are meant to be run for specific environments.
    | The options below specify whether we should skip environment specific
    | analyzers if the environment does not match.
    |
    */
    'skip_env_specific' => env('LARABEACON_SKIP_ENVIRONMENT_SPECIFIC', false),

    /*
    |--------------------------------------------------------------------------
    | Guest URL
    |--------------------------------------------------------------------------
    |
    | Specify any guest URL or path (preferably your app's login URL) here. A
    | full production URL lets a local LaraBeacon run inspect live HTTP headers.
    | Examples: '/login' or 'https://example.com/login'.
    |
    */
    'guest_url' => env('LARABEACON_GUEST_URL'),

    /*
    |--------------------------------------------------------------------------
    | Exclusions From Reporting
    |--------------------------------------------------------------------------
    |
    | Specify the analyzer classes that you wish to exclude from reporting. This
    | means that if any of these analyzers fail, they will not be counted
    | towards the exit status of the LaraBeacon command. This is useful
    | if you wish to run the command in your CI/CD pipeline.
    | Example: [\BaerSoftware\LaraBeacon\Analyzers\Security\XSSAnalyzer::class].
    |
    */
    'dont_report' => [],

    /*
    |--------------------------------------------------------------------------
    | Ignoring Errors
    |--------------------------------------------------------------------------
    |
    | Use this config option to ignore specific errors. The key of this array
    | would be the analyzer class and the value would be an associative
    | array with path and details. Run php artisan larabeacon:baseline
    | to auto-generate this. Patterns are supported in details.
    |
    */
    'ignore_errors' => [],

    /*
    |--------------------------------------------------------------------------
    | Analyzer Configurations
    |--------------------------------------------------------------------------
    |
    | The following configuration options pertain to individual analyzers.
    | These are recommended options but feel free to customize them based
    | on your application needs.
    |
    */
    'license_whitelist' => [
        'Apache-2.0', 'Apache2', 'BSD-2-Clause', 'BSD-3-Clause', 'LGPL-2.1-only', 'LGPL-2.1',
        'LGPL-2.1-or-later', 'LGPL-3.0', 'LGPL-3.0-only', 'LGPL-3.0-or-later', 'MIT', 'ISC',
        'CC0-1.0', 'Unlicense', 'WTFPL',
    ],

    /*
    |--------------------------------------------------------------------------
    | LaraBeacon Cloud
    |--------------------------------------------------------------------------
    |
    | Cloud reporting is optional and disabled until an HTTPS endpoint is configured.
    | The public package remains fully usable offline while LaraBeacon Cloud can
    | later be connected without coupling the analyzer core to the SaaS.
    |
    */
    'cloud' => [
        'endpoint' => env('LARABEACON_CLOUD_ENDPOINT'),
        'username' => env('LARABEACON_CLOUD_USERNAME'),
        'api_token' => env('LARABEACON_CLOUD_API_TOKEN'),
        'include_code_snippets' => env('LARABEACON_CLOUD_INCLUDE_CODE_SNIPPETS', false),
        'include_exception_stack_traces' => env('LARABEACON_CLOUD_INCLUDE_EXCEPTION_STACK_TRACES', false),
    ],

    // Set this value to your GitHub repository for future LaraBeacon Cloud integration.
    // Format: "myorg/myrepo" like "laravel/framework".
    'github_repo' => env('LARABEACON_GITHUB_REPO'),

    // Set to true to restrict the max number of files displayed in the LaraBeacon
    // command for each check. Set to false to display all files.
    'compact_lines' => true,

    // List your commercial packages (licensed by you) below, so that they are not
    // flagged by the License Analyzer.
    'commercial_packages' => [
        'baer-software/larabeacon-pro',
    ],

    // Minified bundles usually contain a few very long lines. These thresholds
    // avoid false positives for modern Vite builds while still flagging normal,
    // human-formatted JavaScript and CSS.
    'minification' => [
        'max_lines' => 10,
        'min_average_line_length' => 120,
    ],

    'allowed_permissions' => [
        base_path() => '775',
        app_path() => '775',
        resource_path() => '775',
        storage_path() => '775',
        public_path() => '775',
        config_path() => '775',
        database_path() => '775',
        base_path('routes') => '775',
        app()->bootstrapPath() => '775',
        app()->bootstrapPath('cache') => '775',
        app()->bootstrapPath('app.php') => '664',
        base_path('artisan') => '775',
        public_path('index.php') => '664',
        public_path('server.php') => '664',
    ],

    'writable_directories' => [
        storage_path(),
        app()->bootstrapPath('cache'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PHPStan Runtime configurations
    |--------------------------------------------------------------------------
    |
    | PHPStan analyzes the whole application in a worker process. Configure its
    | memory limit independently so common 128 MB CLI defaults do not abort it.
    */
    'phpstan' => [
        '--error-format' => 'json',
        '--no-progress' => true,
        '--memory-limit' => env('LARABEACON_PHPSTAN_MEMORY_LIMIT', '1G'),
    ],
];
