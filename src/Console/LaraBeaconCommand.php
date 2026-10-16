<?php

namespace BaerSoftware\LaraBeacon\Console;

use BaerSoftware\LaraBeacon\Console\Formatters\AnsiFormatter;
use BaerSoftware\LaraBeacon\Findings\Baseline;
use BaerSoftware\LaraBeacon\Findings\Exporter;
use BaerSoftware\LaraBeacon\Findings\FixPreview;
use BaerSoftware\LaraBeacon\Findings\Report;
use BaerSoftware\LaraBeacon\Findings\ScanProfile;
use BaerSoftware\LaraBeacon\LaraBeacon;
use BaerSoftware\LaraBeacon\Reporting\API;
use BaerSoftware\LaraBeacon\Reporting\JsonReportBuilder;
use Illuminate\Console\Command;

class LaraBeaconCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larabeacon
                            {analyzer?* : The analyzer class that you wish to run}
                            {--format=terminal : terminal, json, markdown or sarif}
                            {--output= : Write the local report to this file}
                            {--profile= : local, ci or production}
                            {--preflight : Explain the selected environment without executing checks}
                            {--baseline= : Load a finding-level JSON acceptance baseline}
                            {--only-new : Fail only for findings not accepted by the baseline}
                            {--fail-on=info : Minimum blocking severity: info, minor, major or critical}
                            {--fix-preview : Include a reviewable diff for supported deterministic fixes}
                            {--details : Show details of each failed check}
                            {--ci : Run LaraBeacon in CI mode}
                            {--report : Send this run to LaraBeacon Cloud}
                            {--review : Request a pull request review from LaraBeacon Cloud}
                            {--show-exceptions : Display the stack trace of exceptions if any}
                            {--issue= : The pull request number for LaraBeacon Cloud}
                            {--branch= : Branch name for Cloud history isolation}
                            {--hash= : An optional commit hash to report to LaraBeacon Cloud}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect your Laravel application with LaraBeacon.';

    /**
     * The final result of the analysis.
     *
     * @var array
     */
    public $result = [];

    /**
     * The number of analyzers to run.
     *
     * @var int
     */
    protected $totalAnalyzers;

    /**
     * The number of analyzers that have completed their analysis.
     *
     * @var int
     */
    protected $countAnalyzers;

    /**
     * The analyzer classes to run. All classes will run if empty.
     *
     * @var array
     */
    protected $analyzerClasses;

    /**
     * @var \BaerSoftware\LaraBeacon\Console\Formatters\Formatter
     */
    protected $formatter;

    /**
     * @var array
     */
    protected $analyzerInfos = [];

    /**
     * Execute the console command.
     *
     * @param \BaerSoftware\LaraBeacon\Reporting\API $api
     * @return int
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function handle()
    {
        $originalConfig = config('larabeacon');
        $originalFilter = LaraBeacon::$filterCallback;

        try {
            return $this->runAnalysis();
        } catch (\InvalidArgumentException|\JsonException $exception) {
            $this->error($exception->getMessage());

            return 2;
        } finally {
            config()->set('larabeacon', $originalConfig);
            LaraBeacon::$filterCallback = $originalFilter;
        }
    }

    protected function runAnalysis(): int
    {
        $format = $this->option('format');
        if (! in_array($format, ['terminal', 'json', 'markdown', 'sarif'], true)
            || ! in_array($this->option('fail-on'), ['info', 'minor', 'major', 'critical'], true)) {
            throw new \InvalidArgumentException('Invalid format or severity threshold.');
        }
        if ($this->option('only-new') && ! $this->option('baseline')) {
            throw new \InvalidArgumentException('--only-new requires --baseline.');
        }
        if ($this->option('output') && $format === 'terminal') {
            throw new \InvalidArgumentException('--output requires json, markdown or sarif format.');
        }
        $entries = $this->option('baseline') ? Baseline::load($this->option('baseline')) : [];
        ScanProfile::apply($this->option('profile'), (bool) $this->option('ci'));
        $this->analyzerInfos = [];
        $this->analyzerClasses = $this->argument('analyzer');
        if ($this->option('preflight')) {
            $this->output->write(json_encode(ScanProfile::preflight($this->option('profile')), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");

            return 0;
        }

        $this->formatter = new AnsiFormatter;

        if ($format === 'terminal') {
            $this->formatter->beforeAnalysis($this);
        }

        if ($this->option('ci')) {
            LaraBeacon::filterAnalyzersForCI();
        }

        LaraBeacon::register($this->analyzerClasses);

        $this->totalAnalyzers = LaraBeacon::totalAnalyzers();
        $this->countAnalyzers = 1;
        $this->initializeResult();

        LaraBeacon::using([$this, 'printAnalyzerOutput']);
        LaraBeacon::run($this->laravel);

        if ($format === 'terminal') {
            $this->formatter->afterAnalysis($this, empty($this->analyzerClasses));
        }
        $localReport = Baseline::annotate(Report::build($this->analyzerInfos), $entries);
        $localReport['scan'] = ScanProfile::preflight($this->option('profile'));
        if ($this->option('fix-preview')) {
            $localReport['fix_previews'] = FixPreview::build($localReport);
        }
        if ($format !== 'terminal') {
            $rendered = Exporter::render($localReport, $format);
            if ($path = $this->option('output')) {
                if (@file_put_contents($path, $rendered) === false) {
                    throw new \InvalidArgumentException('Could not write report: '.$path);
                }
            } else {
                $this->output->write($rendered);
            }
        } elseif ($this->option('fix-preview')) {
            foreach ($localReport['fix_previews'] as $preview) {
                $this->output->write($preview['diff']);
            }
            if ($localReport['fix_previews'] === []) {
                $this->line('No deterministic fix preview is available for these findings. Follow the remediation guidance.');
            }
        }
        if ($format === 'terminal' && $this->option('baseline')) {
            $this->line(count(array_filter($localReport['findings'], fn ($finding) => $finding['accepted'])).' accepted findings; '.count($localReport['stale_baseline_entries']).' baseline entries absent from this scan (review scope before removing).');
        }

        if ($this->option('report')) {
            $api = $this->laravel->make(API::class);
            $reportBuilder = new JsonReportBuilder();

            $metadata = [];

            if ($github_issue = $this->option('issue')) {
                $metadata = compact('github_issue');
            }

            if ($this->option('review')) {
                $metadata['needs_review'] = true;
            }

            if ($this->option('ci') || $this->option('profile') === 'ci') {
                $metadata['trigger'] = 'ci';
            }

            if ($branch = $this->option('branch')) {
                $metadata['branch'] = $branch;
            }

            if ($hash = $this->option('hash')) {
                $metadata['commit_id'] = $hash;
            }

            $url = $api->sendReport($reportBuilder->buildReport($this->analyzerInfos, $this->result, $metadata));

            if (! is_null($url) && $format === 'terminal') {
                $this->getOutput()->newLine();
                $this->comment("Your report can be viewed at <href={$url}>{$url}</>");
            } elseif (! is_null($url) && $this->option('output')) {
                // The report went to a file, so stdout can carry the Cloud link for agents and scripts.
                $this->line('LaraBeacon Cloud report: '.$url);
            }
        }

        if ($format === 'terminal' && ! $this->option('ci') && $this->option('profile') !== 'ci' && $this->input->isInteractive() && ! $this->getOutput()->isQuiet()) {
            if (! class_exists('BaerSoftware\\LaraBeaconPro\\LaraBeaconProServiceProvider')) {
                $this->newLine();
                $this->line('LaraBeacon Pro adds deeper runtime, infrastructure, and security checks for one-time project pricing: https://larabeacon.com/pro');
            }

            $this->newLine();
            $this->line('LaraBeacon Cloud: report history and team access. Create an account: https://larabeacon.com/register');
            $this->line('Core works offline. Cloud uploads require --report and configured credentials.');
            $this->newLine();
            $this->line('Using LaraBeacon? The first five external projects can get a free Pro licence for one project in return for 20 minutes of honest feedback.');
            $this->line('Share installation feedback, useful findings, and false positives: anthony.baer@baer-software.com');
        }

        return Baseline::blocks($localReport, $this->option('fail-on'), (bool) $this->option('only-new')) ? 1 : 0;
    }

    /**
     * @param array $info
     *
     * @return void
     */
    public function printAnalyzerOutput(array $info)
    {
        $this->analyzerInfos[] = $info;

        if ($this->option('format') === 'terminal') {
            $this->formatter->parseAnalyzerResult(
                $this,
                $info,
                $this->countAnalyzers,
                $this->totalAnalyzers,
                empty($this->analyzerClasses)
            );
        }

        $this->updateResult($info);

        $this->countAnalyzers++;
    }

    /**
     * Initialize the result.
     *
     * @return $this
     */
    protected function initializeResult()
    {
        $this->result = [];

        foreach (array_merge(LaraBeacon::$categories, ['Total']) as $category) {
            $this->result[$category] = [
                'passed' => 0,
                'failed' => 0,
                'skipped' => 0,
                'error' => 0,
                'reported' => 0,
            ];
        }

        return $this;
    }

    /**
     * Update the result based on the analysis.
     *
     * @param array $info
     * @return string
     */
    protected function updateResult(array $info)
    {
        $this->result[$info['category']][$info['status']]++;
        $this->result['Total'][$info['status']]++;
        if (in_array($info['status'], ['failed', 'error'], true) && ($info['reportable'] ?? true)) {
            $this->result[$info['category']]['reported']++;
            $this->result['Total']['reported']++;
        }
    }
}
