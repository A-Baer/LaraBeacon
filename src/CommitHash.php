<?php

namespace BaerSoftware\LaraBeacon;

class CommitHash
{
    /**
     * @return string
     */
    public static function get()
    {
        return trim(exec('cd '.app_path().' && git log --pretty="%h" -n1 HEAD 2> /dev/null'));
    }

    /**
     * The checked-out Git branch, or null for a detached HEAD or outside a repository.
     */
    public static function branch(): ?string
    {
        $branch = trim((string) exec('cd '.escapeshellarg(app_path()).' && git rev-parse --abbrev-ref HEAD 2> /dev/null'));

        return $branch === '' || $branch === 'HEAD' || preg_match('#^[A-Za-z0-9._/-]{1,200}$#', $branch) !== 1 ? null : $branch;
    }
}
