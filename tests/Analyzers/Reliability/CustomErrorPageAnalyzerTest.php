<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Reliability;

use BaerSoftware\LaraBeacon\Analyzers\Reliability\CustomErrorPageAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithMiddleware;

class CustomErrorPageAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithMiddleware;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(CustomErrorPageAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skipped_for_stateless_apps()
    {
        $this->runLaraBeacon();

        $this->assertSkipped(CustomErrorPageAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_no_custom_error_pages()
    {
        $this->registerStatefulGlobalMiddleware();

        $this->runLaraBeacon();

        $this->assertFailed(CustomErrorPageAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_custom_error_pages()
    {
        $this->registerStatefulGlobalMiddleware();
        $this->app->config->set('view.paths', [$this->getViewStubPath()]);

        $this->runLaraBeacon();

        $this->assertPassed(CustomErrorPageAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_custom_error_namespace()
    {
        $this->registerStatefulGlobalMiddleware();
        $this->app['view']->replaceNamespace('errors', $this->getViewStubPath().DIRECTORY_SEPARATOR.'errors');

        $this->runLaraBeacon();

        $this->assertPassed(CustomErrorPageAnalyzer::class);
    }
}
