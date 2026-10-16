<?php

declare(strict_types=1);

// stdout carries the protocol (or hook context); diagnostics go to stderr.
ini_set('display_errors', 'stderr');

/*
 * LaraBeacon MCP server for Claude Code.
 *
 * A dependency-free stdio MCP server. It starts even when LaraBeacon is not yet installed in the
 * project, so it can guide the installation, and it delegates every scan and Cloud request to the
 * project's own `php artisan` commands. Credentials stay in the application's configuration and are
 * never read or returned by this process.
 */

if (PHP_VERSION_ID < 80200) {
    fwrite(STDERR, "The LaraBeacon MCP server requires PHP 8.2 or newer.\n");
    exit(1);
}

require __DIR__.'/src/Server.php';
require __DIR__.'/src/Project.php';
require __DIR__.'/src/Artisan.php';
require __DIR__.'/src/Catalog.php';
require __DIR__.'/src/Tools.php';

$projectArgument = null;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--project=')) {
        $projectArgument = substr($argument, strlen('--project='));
    }
}

$project = LaraBeaconMcp\Project::resolve($projectArgument, getenv('CLAUDE_PROJECT_DIR') ?: null, getcwd() ?: '.');
$catalog = LaraBeaconMcp\Catalog::load(dirname(__DIR__).'/data/checks.json');
$timeout = (int) (getenv('LARABEACON_MCP_TIMEOUT') ?: 600);
$artisan = new LaraBeaconMcp\Artisan($project, PHP_BINARY, max(30, $timeout));
$tools = new LaraBeaconMcp\Tools($project, $artisan, $catalog);

(new LaraBeaconMcp\Server($tools))->serve(STDIN, STDOUT);
