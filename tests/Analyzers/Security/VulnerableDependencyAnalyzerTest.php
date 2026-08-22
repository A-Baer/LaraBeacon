<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\VulnerableDependencyAnalyzer;
use BaerSoftware\LaraBeacon\Composer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;

class VulnerableDependencyAnalyzerTest extends AnalyzerTestCase
{
    use InteractsWithComposer;

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->replaceComposer($app);

        $this->setupEnvironmentFor(VulnerableDependencyAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function confirms_larabeacon_has_no_vulnerable_dependencies()
    {
        if (Str::startsWith(Application::VERSION, '7')) {
            // Since 7.x is no longer receiving security updates, we need to skip this version.
            $this->markTestSkipped();
        }

        $this->runLaraBeacon();

        $this->assertPassed(VulnerableDependencyAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_vulnerable_dependencies()
    {
        app(Composer::class)->setWorkingPath($this->getBaseStubPath());

        $this->runLaraBeacon();

        $this->assertFailed(VulnerableDependencyAnalyzer::class);
        $this->assertErrorMessageContains(VulnerableDependencyAnalyzer::class, 'laravel/framework');
        $this->assertErrorMessageContains(VulnerableDependencyAnalyzer::class, '8.22.0');
        $this->assertErrorMessageContains(VulnerableDependencyAnalyzer::class, 'Unexpected bindings in QueryBuilder');
    }
}
