<?php

namespace BaerSoftware\LaraBeacon\Console;

use BaerSoftware\LaraBeacon\Analyzers\Trace;
use BaerSoftware\LaraBeacon\CodeCorrection\ConfigManipulator;
use BaerSoftware\LaraBeacon\LaraBeacon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class BaselineCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larabeacon:baseline
                            {--ci : Run LaraBeacon in CI mode}
                            {--output= : Write a finding-level JSON baseline instead of editing config}
                            {--reason= : Required acceptance reason for JSON baseline entries}
                            {--expires= : Optional YYYY-MM-DD expiry}
                            {--profile= : local, ci or production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Declare the currently reported errors as the baseline to avoid reporting them in subsequent runs.';

    /**
     * @var array
     */
    protected $dont_report = [];

    protected $results = [];

    /**
     * @var array
     */
    protected $ignore_errors = [];

    /**
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $progressbar;

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \ReflectionException|\Illuminate\Contracts\Container\BindingResolutionException|\Throwable
     */
    public function handle()
    {
        $config = config('larabeacon');
        $filter = LaraBeacon::$filterCallback;

        try {
            return $this->generate();
        } catch (\InvalidArgumentException|\JsonException $exception) {
            $this->error($exception->getMessage());

            return 2;
        } finally {
            config()->set('larabeacon', $config);
            LaraBeacon::$filterCallback = $filter;
        }
    }

    protected function generate(): int
    {
        if ($this->option('output') && trim((string) $this->option('reason')) === '') {
            throw new \InvalidArgumentException('A JSON baseline requires --reason.');
        }
        \BaerSoftware\LaraBeacon\Findings\Baseline::validateExpiry($this->option('expires'));
        \BaerSoftware\LaraBeacon\Findings\ScanProfile::apply($this->option('profile'), (bool) $this->option('ci'));
        $this->results = $this->dont_report = $this->ignore_errors = [];
        $this->setColors();
        $this->line(require __DIR__.DIRECTORY_SEPARATOR.'logo.php');
        $this->output->newLine();
        $this->line('Please wait while LaraBeacon scans your code base...');
        $this->output->newLine();

        // Reset ignored errors to establish a complete baseline.
        config()->set('larabeacon.ignore_errors', []);
        config()->set('larabeacon.dont_report', []);

        if ($this->option('ci')) {
            LaraBeacon::filterAnalyzersForCI();
        }

        LaraBeacon::register();

        $this->progressbar = $this->output->createProgressBar(count(LaraBeacon::$analyzerClasses));

        LaraBeacon::using([$this, 'parseAnalyzerResult']);
        LaraBeacon::run($this->laravel);

        $this->progressbar->finish();
        $this->output->newLine();

        if ($path = $this->option('output')) {
            $report = \BaerSoftware\LaraBeacon\Findings\Report::build($this->results);
            if (collect($this->results)->contains(fn ($result) => $result['status'] === 'error')) {
                $this->error('Baseline was not written because at least one check could not complete.');

                return 2;
            }
            $old = is_file($path) ? \BaerSoftware\LaraBeacon\Findings\Baseline::load($path) : [];
            $old = array_column($old, null, 'fingerprint');
            $entries = [];
            foreach ($report['findings'] as $finding) {
                $entries[] = $old[$finding['fingerprint']] ?? [
                    'fingerprint' => $finding['fingerprint'], 'analyzer' => $finding['analyzer'],
                    'location' => $finding['location'], 'reason' => $this->option('reason'),
                    'expires_at' => $this->option('expires'),
                ];
                unset($old[$finding['fingerprint']]);
            }
            // Prune only findings of checks that actually completed in this scan.
            $completed = array_column(array_filter($this->results, fn ($result) => in_array($result['status'], ['passed', 'failed'], true)), 'class');
            foreach ($old as $entry) {
                if (! in_array($entry['analyzer'] ?? null, $completed, true)) {
                    $entries[] = $entry;
                }
            }
            $json = json_encode(['schema_version' => 1, 'entries' => $entries], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
            if (@file_put_contents($path, $json) === false) {
                throw new \InvalidArgumentException('Could not write baseline: '.$path);
            }
            $this->info('Finding baseline written; existing reasons and expiry dates retained.');
        } else {
            $this->updateConfig();
        }

        return 0;
    }

    /**
     * Parse the result of each analyzer.
     *
     * @param array $info
     * @return void
     */
    public function parseAnalyzerResult(array $info)
    {
        $this->results[] = $info;
        if ($info['status'] === 'failed' && empty($info['traces'])) {
            $this->dont_report[] = $info['class'];
        }

        if (empty($info['traces'])) {
            return;
        }

        collect($info['traces'])->each(function (Trace $trace) use ($info) {
            if (is_null($trace->details)) {
                if (! in_array($info['class'], $this->dont_report)) {
                    $this->dont_report[] = $info['class'];
                }
            } else {
                if (! isset($this->ignore_errors[$info['class']])) {
                    $this->ignore_errors[$info['class']] = [];
                }

                $this->ignore_errors[$info['class']][] = [
                    'path' => Str::contains($trace->path, base_path()) ?
                        trim(Str::after($trace->path, base_path()), '/') : $trace->path,
                    'details' => $trace->details,
                ];
            }
        });

        $this->progressbar->advance();
    }

    /**
     * Update the config file for new baseline.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function updateConfig()
    {
        if (! file_exists(config_path('larabeacon.php'))) {
            $this->call('vendor:publish', ['--tag' => 'larabeacon']);
        }

        if (LaraBeacon::isPro() && ! file_exists(config_path('larabeacon-pro.php'))) {
            $this->call('vendor:publish', ['--tag' => 'larabeacon-pro']);
        }

        (new ConfigManipulator)->replace(config_path('larabeacon.php'), [
            'dont_report' => $this->dont_report,
            'ignore_errors' => $this->ignore_errors,
        ]);

        $this->info('Successfully updated config/larabeacon.php with new baseline values.');
    }

    /**
     * Set the console colors for LaraBeacon's logo.
     *
     * @return void
     */
    protected function setColors()
    {
        collect([
            'lb' => 'green',
        ])->each(function ($color, $tag) {
            $this->output->getFormatter()->setStyle($tag, new OutputFormatterStyle($color));
        });
    }
}
