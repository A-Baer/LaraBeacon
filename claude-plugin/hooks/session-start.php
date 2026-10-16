<?php

declare(strict_types=1);

// stdout carries the protocol (or hook context); diagnostics go to stderr.
ini_set('display_errors', 'stderr');

// Adds one line of context in Laravel projects; prints nothing elsewhere.

require dirname(__DIR__).'/server/src/Project.php';

$project = LaraBeaconMcp\Project::resolve(null, getenv('CLAUDE_PROJECT_DIR') ?: null, getcwd() ?: '.');

if (! $project->isLaravel()) {
    exit(0);
}

if ($project->installed(LaraBeaconMcp\Project::CORE_PACKAGE)) {
    $pro = $project->installed(LaraBeaconMcp\Project::PRO_PACKAGE) ? ' with LaraBeacon Pro' : '';
    echo "LaraBeacon{$pro} is installed in this Laravel project. Before finishing changes to configuration, security, queues, caching or deployment, or when asked about production readiness, run a scan with the larabeacon_scan tool (skill: /larabeacon:scan).\n";
} else {
    echo "The LaraBeacon plugin is enabled but LaraBeacon is not installed in this Laravel project. If the user asks about production readiness, security or performance checks, offer /larabeacon:setup (free Core package, runs locally).\n";
}
