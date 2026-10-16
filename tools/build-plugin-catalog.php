<?php

declare(strict_types=1);

// Regenerates claude-plugin/data/checks.json from the public website catalogue (config/checks.php).
// Usage: php tools/build-plugin-catalog.php website/config/checks.php

$source = $argv[1] ?? dirname(__DIR__).'/website/config/checks.php';

if (! is_file($source)) {
    fwrite(STDERR, "Catalogue source not found: {$source}\n");
    exit(1);
}

$checks = require $source;

// Composer packages or project files that make a Pro check directly relevant to a project.
$signals = [
    'telescope-' => ['packages' => ['laravel/telescope']],
    'horizon-' => ['packages' => ['laravel/horizon']],
    'nova-' => ['packages' => ['laravel/nova']],
    'redis-' => ['packages' => ['predis/predis', 'laravel/horizon']],
    'queue-blocking' => ['packages' => ['predis/predis', 'laravel/horizon']],
    'vite-' => ['files' => ['vite.config.js', 'vite.config.ts', 'vite.config.mjs']],
    'cache-busting' => ['files' => ['vite.config.js', 'vite.config.ts', 'vite.config.mjs', 'webpack.mix.js']],
];

$entries = [];

foreach ($checks as $check) {
    $entry = [
        'slug' => $check['slug'],
        'name' => $check['name'],
        'tier' => $check['tier'],
        'category' => $check['category'],
        'severity' => $check['severity'],
        'time_to_fix' => $check['time_to_fix'],
        'summary' => $check['summary'],
        'analyzer' => ($check['tier'] === 'pro' ? 'BaerSoftware\\LaraBeaconPro' : 'BaerSoftware\\LaraBeacon')
            .'\\Analyzers\\'.ucfirst($check['category']).'\\'.$check['class'],
        'url' => 'https://larabeacon.com/checks/'.$check['slug'],
    ];

    foreach ($signals as $prefix => $signal) {
        if (str_starts_with($check['slug'], $prefix)) {
            $entry['relevant_when'] = $signal;
            break;
        }
    }

    $entries[] = $entry;
}

$target = dirname(__DIR__).'/claude-plugin/data/checks.json';
file_put_contents($target, json_encode([
    'generated_at' => gmdate('Y-m-d'),
    'checks' => $entries,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");

echo count($entries)." checks written to {$target}\n";
