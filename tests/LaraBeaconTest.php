<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;
use BaerSoftware\LaraBeacon\Analyzers\Performance\CacheHeaderAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Performance\SessionDriverAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Reliability\DeadCodeAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\AppDebugAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\CSRFAnalyzer;
use BaerSoftware\LaraBeacon\LaraBeacon;
use BaerSoftware\LaraBeacon\Tests\Stubs\CustomCategoryStub;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;

class LaraBeaconTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->config->set('larabeacon.analyzers', AppDebugAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registers_analyzer_classes()
    {
        LaraBeacon::register();

        $this->assertContains(AppDebugAnalyzer::class, LaraBeacon::$analyzerClasses);
        $this->assertNotContains(Analyzer::class, LaraBeacon::$analyzerClasses);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function registers_analyzer_categories()
    {
        $this->app->config->set(
            'larabeacon.analyzers',
            [
                CSRFAnalyzer::class,
                SessionDriverAnalyzer::class,
                DeadCodeAnalyzer::class,
                CustomCategoryStub::class,
            ]
        );

        LaraBeacon::register();

        $this->assertEquals(['Security', 'Performance', 'Reliability', 'Custom'], LaraBeacon::$categories);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function excludes_analyzer_classes()
    {
        $this->app->config->set('larabeacon.analyzers', '*');
        $this->app->config->set('larabeacon.exclude_analyzers', [AppDebugAnalyzer::class]);

        LaraBeacon::register();

        $this->assertNotContains(AppDebugAnalyzer::class, LaraBeacon::$analyzerClasses);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function filters_analyzer_classes_for_ci()
    {
        $this->app->config->set('larabeacon.analyzers', '*');

        LaraBeacon::filterAnalyzersForCI();
        LaraBeacon::register();

        $this->assertNotContains(CacheHeaderAnalyzer::class, LaraBeacon::$analyzerClasses);
        $this->assertContains(DeadCodeAnalyzer::class, LaraBeacon::$analyzerClasses);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function excludes_analyzer_classes_for_ci()
    {
        $this->app->config->set('larabeacon.analyzers', '*');
        $this->app->config->set('larabeacon.ci_mode_exclude_analyzers', [DeadCodeAnalyzer::class]);

        LaraBeacon::filterAnalyzersForCI();
        LaraBeacon::register();

        $this->assertNotContains(DeadCodeAnalyzer::class, LaraBeacon::$analyzerClasses);
        $this->assertNotContains(CacheHeaderAnalyzer::class, LaraBeacon::$analyzerClasses);
        $this->assertContains(AppDebugAnalyzer::class, LaraBeacon::$analyzerClasses);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function allows_overriding_analyzer_classes_for_ci()
    {
        $this->app->config->set('larabeacon.analyzers', '*');
        $this->app->config->set('larabeacon.ci_mode_analyzers', [CacheHeaderAnalyzer::class, DeadCodeAnalyzer::class]);

        LaraBeacon::filterAnalyzersForCI();
        LaraBeacon::register();

        $this->assertContains(CacheHeaderAnalyzer::class, LaraBeacon::$analyzerClasses);
        $this->assertContains(DeadCodeAnalyzer::class, LaraBeacon::$analyzerClasses);
        $this->assertCount(2, LaraBeacon::$analyzerClasses);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function runs_analyzer_classes()
    {
        LaraBeacon::register();

        $appDebugAnalyzer = m::mock(AppDebugAnalyzer::class);
        $appDebugAnalyzer->shouldReceive('run')->once();

        $this->app->singleton(AppDebugAnalyzer::class, function () use ($appDebugAnalyzer) {
            return $appDebugAnalyzer;
        });

        LaraBeacon::run($this->app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function parses_analyzer_classes_recursively()
    {
        $this->app->config->set('larabeacon.analyzers', '*');

        $this->assertContains(AppDebugAnalyzer::class, LaraBeacon::getAnalyzerClasses());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function published_configuration_keeps_the_package_analyzer_path()
    {
        $files = new Filesystem();
        $directory = sys_get_temp_dir().'/larabeacon-published-config-'.bin2hex(random_bytes(6));
        $files->makeDirectory($directory);
        $path = $directory.'/larabeacon.php';
        $files->copy(__DIR__.'/../config/larabeacon.php', $path);

        try {
            $publishedConfig = require $path;

            $this->assertSame(
                LaraBeacon::analyzerPath(),
                $publishedConfig['analyzer_paths']['BaerSoftware\\LaraBeacon\\Analyzers']
            );
            $this->assertDirectoryExists(LaraBeacon::analyzerPath());
        } finally {
            $files->deleteDirectory($directory);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function runs_before_running_callback()
    {
        LaraBeacon::register();

        $ran = false;

        LaraBeacon::beforeRunning(function () use (&$ran) {
            $ran = true;
        });

        $appDebugAnalyzer = m::mock(AppDebugAnalyzer::class);
        $appDebugAnalyzer->shouldReceive('run')->once();

        $this->app->singleton(AppDebugAnalyzer::class, function () use ($appDebugAnalyzer) {
            return $appDebugAnalyzer;
        });

        LaraBeacon::run($this->app);

        $this->assertTrue($ran);
    }
}
