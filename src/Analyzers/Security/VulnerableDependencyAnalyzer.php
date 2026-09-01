<?php

namespace BaerSoftware\LaraBeacon\Analyzers\Security;

use BaerSoftware\LaraBeacon\Composer;
use Enlightn\SecurityChecker\AdvisoryAnalyzer;
use Enlightn\SecurityChecker\AdvisoryFetcher;
use Enlightn\SecurityChecker\AdvisoryParser;
use Enlightn\SecurityChecker\Composer as SecurityCheckerComposer;
use RuntimeException;
use Throwable;

class VulnerableDependencyAnalyzer extends SecurityAnalyzer
{
    /**
     * The title describing the analyzer.
     *
     * @var string|null
     */
    public $title = 'Your application does not rely on backend dependencies with known security issues.';

    /**
     * The severity of the analyzer.
     *
     * @var string|null
     */
    public $severity = self::SEVERITY_CRITICAL;

    /**
     * The time to fix in minutes.
     *
     * @var int|null
     */
    public $timeToFix = 60;

    /**
     * The result of the vulnerability scan.
     *
     * @var array
     */
    public $result;

    /**
     * Get the error message describing the analyzer insights.
     *
     * @return string
     */
    public function errorMessage()
    {
        return "Your application has a total of ".count($this->result)." known vulnerabilities in the application "
            ."dependencies. This can be very dangerous and you must resolve this by either applying patch updates or "
            ."removing the vulnerable dependencies. The packages which have these vulnerabilities include: "
            .PHP_EOL.$this->listVulnerablePackages();
    }

    /**
     * Execute the analyzer.
     *
     * @param \BaerSoftware\LaraBeacon\Composer $composer
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(Composer $composer)
    {
        $this->withAdvisoryLock(function () use ($composer): void {
            $parser = new AdvisoryParser((new AdvisoryFetcher)->fetchAdvisories());

            $dependencies = (new SecurityCheckerComposer)->getDependencies($composer->getLockFile());

            $this->result = (new AdvisoryAnalyzer($parser->getAdvisories()))->analyzeDependencies($dependencies);
        });

        if (count($this->result) > 0) {
            $this->markFailed();
        }
    }

    /**
     * Run the advisory fetch, extraction and analysis while holding a host-wide lock.
     *
     * The upstream security checker uses fixed paths in the system temporary
     * directory, so concurrent LaraBeacon processes must not access them at once.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    protected function withAdvisoryLock(callable $callback)
    {
        $lock = fopen($this->advisoryLockPath(), 'c');

        if ($lock === false) {
            throw new RuntimeException('Unable to open the LaraBeacon security advisory lock file.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Unable to acquire the LaraBeacon security advisory lock.');
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function advisoryLockPath(): string
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'larabeacon-security-advisories.lock';
    }

    /**
     * List the vulnerable packages.
     *
     * @return string
     */
    public function listVulnerablePackages()
    {
        try {
            return collect($this->result)
                ->map(function ($vulnerability, $package) {
                    return $package.' ('.$vulnerability['version'].'): '.
                        collect(data_get($vulnerability, 'advisories.*.title'))
                        ->join(', ', ' and ');
                })->values()->implode(PHP_EOL);
        } catch (Throwable $e) {
            return json_encode($this->result);
        }
    }
}
