<?php

namespace BaerSoftware\LaraBeacon\Console;

use BaerSoftware\LaraBeacon\Reporting\API;
use BaerSoftware\LaraBeacon\Reporting\BadResponseException;
use BaerSoftware\LaraBeacon\Reporting\UnauthorizedException;
use Composer\InstalledVersions;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;

class CloudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larabeacon:cloud
                            {action=status : status, reports or report}
                            {report=latest : A report number or "latest" for the report action}
                            {--limit=10 : Number of reports to list (1-50)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read LaraBeacon Cloud status and report history as JSON.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');

        try {
            $data = match ($action) {
                'status' => $this->status(),
                'reports' => ['reports' => $this->api()->reports((int) $this->option('limit'))['reports'] ?? []],
                'report' => $this->api()->report((string) $this->argument('report')),
                default => throw new \InvalidArgumentException('Unknown action. Use status, reports or report.'),
            };
        } catch (\InvalidArgumentException|\LogicException|UnauthorizedException|BadResponseException|GuzzleException $exception) {
            $this->write(['error' => $this->safeMessage($exception)]);

            return $exception instanceof \InvalidArgumentException ? 2 : 1;
        }

        $this->write($data);

        return 0;
    }

    protected function status(): array
    {
        $cloud = config('larabeacon.cloud', []);
        $endpoint = (string) ($cloud['endpoint'] ?? '');

        return [
            'core_version' => $this->version('baer-software/larabeacon'),
            'pro_installed' => class_exists('BaerSoftware\\LaraBeaconPro\\LaraBeaconProServiceProvider'),
            'pro_version' => $this->version('baer-software/larabeacon-pro'),
            'cloud' => [
                'configured' => $endpoint !== '' && ! empty($cloud['username']) && ! empty($cloud['api_token']),
                'endpoint_host' => $endpoint === '' ? null : parse_url($endpoint, PHP_URL_HOST),
                'include_code_snippets' => (bool) ($cloud['include_code_snippets'] ?? false),
                'include_exception_stack_traces' => (bool) ($cloud['include_exception_stack_traces'] ?? false),
            ],
        ];
    }

    protected function api(): API
    {
        return $this->laravel->make(API::class);
    }

    protected function version(string $package): ?string
    {
        if (! class_exists(InstalledVersions::class) || ! InstalledVersions::isInstalled($package)) {
            return null;
        }

        return InstalledVersions::getPrettyVersion($package);
    }

    /**
     * Keep response bodies, tokens and transport internals out of the output.
     */
    protected function safeMessage(\Throwable $exception): string
    {
        return match (true) {
            $exception instanceof UnauthorizedException => 'LaraBeacon Cloud rejected the project credentials.',
            $exception instanceof BadResponseException => 'LaraBeacon Cloud returned an unexpected response.',
            $exception instanceof GuzzleException => 'LaraBeacon Cloud could not be reached.',
            default => $exception->getMessage(),
        };
    }

    protected function write(array $data): void
    {
        $this->output->write(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }
}
