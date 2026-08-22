<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\ViewCachingAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;

class ViewCachingAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(ViewCachingAnalyzer::class, $app);

        $app->config->set('view.paths', [$this->getViewStubPath()]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_non_cached_views_in_production()
    {
        $this->app->config->set('app.env', 'production');

        $this->artisan('view:clear');

        $this->runLaraBeacon();

        $this->assertFailed(ViewCachingAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_cached_views_in_production()
    {
        $this->app->config->set('app.env', 'production');

        $this->artisan('view:cache');

        $this->runLaraBeacon();

        $this->assertPassed(ViewCachingAnalyzer::class);

        $this->artisan('view:clear');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_non_cached_views_in_local()
    {
        $this->app->config->set('app.env', 'local');

        $this->artisan('view:clear');

        $this->runLaraBeacon();

        $this->assertPassed(ViewCachingAnalyzer::class);
    }
}
