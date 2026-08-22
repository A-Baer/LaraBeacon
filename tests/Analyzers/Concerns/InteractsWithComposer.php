<?php


namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns;

use BaerSoftware\LaraBeacon\Composer;

trait InteractsWithComposer
{
    protected function replaceComposer($app)
    {
        $app->singleton(Composer::class, function ($app) {
            return new Composer(
                $app->make('files'),
                __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'
                .DIRECTORY_SEPARATOR.'..'
            );
        });
    }
}
