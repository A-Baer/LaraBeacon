<?php

namespace BaerSoftware\LaraBeacon\Console;

use BaerSoftware\LaraBeacon\Console\Formatters\AnsiFormatter;
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
                            {--details : Show details of each failed check}
                            {--ci : Run LaraBeacon in CI mode}
                            {--report : Send this run to LaraBeacon Cloud}
                            {--review : Request a pull request review from LaraBeacon Cloud}
                            {--show-exceptions : Display the stack trace of exceptions if any}
                            {--issue= : The pull request number for LaraBeacon Cloud}
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
        $this->analyzerClasses = $this->argument('analyzer');

        $this->formatter = new AnsiFormatter;

        $this->formatter->beforeAnalysis($this);

        if ($this->option('ci')) {
            LaraBeacon::filterAnalyzersForCI();
        }

        LaraBeacon::register($this->analyzerClasses);

        $this->totalAnalyzers = LaraBeacon::totalAnalyzers();
        $this->countAnalyzers = 1;
        $this->initializeResult();

        LaraBeacon::using([$this, 'printAnalyzerOutput']);
        LaraBeacon::run($this->laravel);

        $this->formatter->afterAnalysis($this, empty($this->analyzerClasses));

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

            if ($this->option('ci')) {
                $metadata['trigger'] = 'ci';
            }

            if ($hash = $this->option('hash')) {
                $metadata['commit_id'] = $hash;
            }

            $url = $api->sendReport($reportBuilder->buildReport($this->analyzerInfos, $this->result, $metadata));

            if (! is_null($url)) {
                $this->getOutput()->newLine();
                $this->comment("Your report can be viewed at <href={$url}>{$url}</>");
            }
        }

        // Exit with a non-zero exit code if there were failed checks to throw an error on CI environments
        return collect($this->result)->sum(function ($category) {
            return $category['reported'];
        }) == 0 ? 0 : 1;
    }

    /**
     * @param array $info
     *
     * @return void
     */
    public function printAnalyzerOutput(array $info)
    {
        $this->analyzerInfos[] = $info;

        $this->formatter->parseAnalyzerResult(
            $this,
            $info,
            $this->countAnalyzers,
            $this->totalAnalyzers,
            empty($this->analyzerClasses)
        );

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
