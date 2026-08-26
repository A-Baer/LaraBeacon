<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$temporaryDirectory = sys_get_temp_dir().'/larabeacon-core-archive-'.bin2hex(random_bytes(8));
$archiveName = 'larabeacon-core';
$archivePath = $temporaryDirectory.'/'.$archiveName.'.zip';

if (! mkdir($temporaryDirectory, 0700, true) && ! is_dir($temporaryDirectory)) {
    fwrite(STDERR, "Unable to create the package archive directory.\n");
    exit(1);
}

try {
    $process = proc_open(
        ['composer', 'archive', '--format=zip', '--file='.$archiveName, '--dir='.$temporaryDirectory],
        [STDIN, STDOUT, STDERR],
        $pipes,
        $projectRoot,
    );

    if (! is_resource($process) || proc_close($process) !== 0 || ! is_file($archivePath)) {
        fwrite(STDERR, "Unable to build the Core package archive.\n");
        exit(1);
    }

    $archive = new ZipArchive();

    if ($archive->open($archivePath) !== true) {
        fwrite(STDERR, "Unable to inspect the Core package archive.\n");
        exit(1);
    }

    $forbiddenPrefixes = [
        '.phpunit.cache/',
        'apps/',
        'node_modules/',
        'packages/',
        'tools/',
        'vendor/',
        'website/',
        'work/',
    ];
    $forbiddenFiles = [
        '.php_cs.cache',
        '.php-cs-fixer.cache',
        '.phpunit.result.cache',
        'composer.lock',
    ];

    for ($index = 0; $index < $archive->numFiles; $index++) {
        $path = $archive->getNameIndex($index);

        if (! is_string($path)) {
            continue;
        }

        foreach ($forbiddenPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                fwrite(STDERR, "Core archive contains forbidden path: {$path}\n");
                exit(1);
            }
        }

        if (in_array($path, $forbiddenFiles, true)) {
            fwrite(STDERR, "Core archive contains forbidden file: {$path}\n");
            exit(1);
        }
    }

    $archive->close();
    fwrite(STDOUT, "Core package archive boundary verified.\n");
} finally {
    if (is_file($archivePath)) {
        unlink($archivePath);
    }

    if (is_dir($temporaryDirectory)) {
        rmdir($temporaryDirectory);
    }
}
