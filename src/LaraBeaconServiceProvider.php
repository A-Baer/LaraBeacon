<?php

namespace BaerSoftware\LaraBeacon;

use BaerSoftware\LaraBeacon\Inspection\Inspector;
use BaerSoftware\LaraBeacon\Reporting\API;
use BaerSoftware\LaraBeacon\Reporting\Client;
use Illuminate\Support\ServiceProvider;

class LaraBeaconServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/larabeacon.php' => config_path('larabeacon.php'),
            ], 'larabeacon');
        }
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Console\LaraBeaconCommand::class,
            Console\BaselineCommand::class,
        ]);

        $this->mergeConfigFrom(__DIR__.'/../config/larabeacon.php', 'larabeacon');

        $this->app->singleton(Inspector::class);
        $this->app->resolving(Inspector::class, function ($inspector) {
            $inspector->start(LaraBeacon::$filePaths?->toArray() ?? []);
        });

        $this->app->singleton(Composer::class, function ($app) {
            return new Composer($app->make('files'), $app->basePath());
        });

        $this->app->singleton(PHPStan::class, function ($app) {
            return new PHPStan($app->make('files'), $app->basePath());
        });
        $this->app->afterResolving(PHPStan::class, function ($PHPStan) {
            $PHPStan->start(LaraBeacon::$filePaths?->toArray() ?? []);
        });

        $this->app->singleton(NPM::class, function ($app) {
            return new NPM($app->make('files'), $app->basePath());
        });

        $this->app->singleton(API::class, function ($app) {
            $endpoint = $app->config->get('larabeacon.cloud.endpoint');

            if (empty($endpoint)) {
                throw new \LogicException(
                    'LaraBeacon Cloud reporting is not configured. Set LARABEACON_CLOUD_ENDPOINT first.'
                );
            }

            $client = new Client(
                $app->config->get('larabeacon.cloud.username'),
                $app->config->get('larabeacon.cloud.api_token'),
                $endpoint
            );

            return new API($client);
        });
    }
}
