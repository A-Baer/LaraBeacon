<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Performance;

use BaerSoftware\LaraBeacon\Analyzers\Performance\MinificationAnalyzer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use Illuminate\Filesystem\Filesystem;

class MinificationAnalyzerTest extends AnalyzerTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $this->setupEnvironmentFor(MinificationAnalyzer::class, $app);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_local_env()
    {
        $this->app->config->set('app.env', 'local');

        $this->runLaraBeacon();

        $this->assertSkipped(MinificationAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function skips_for_no_assets_to_minify()
    {
        $this->app->config->set('app.env', 'production');

        $this->runLaraBeacon();

        $this->assertSkipped(MinificationAnalyzer::class);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unminified_files()
    {
        $this->app->config->set('app.env', 'production');
        (new Filesystem())->copyDirectory(
            $this->getBaseStubPath().DIRECTORY_SEPARATOR.'unminified_assets',
            public_path('unminified_assets')
        );

        $this->runLaraBeacon();

        $this->assertFailed(MinificationAnalyzer::class);
        $this->assertErrorMessageContains(MinificationAnalyzer::class, 'bootstrap.js');
        $this->assertErrorMessageContains(MinificationAnalyzer::class, 'jquery.js');
        $this->assertErrorMessageContains(MinificationAnalyzer::class, 'bootstrap.css');

        (new Filesystem())->deleteDirectory(public_path('unminified_assets'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_minified_assets()
    {
        $this->app->config->set('app.env', 'production');
        (new Filesystem())->copyDirectory($this->getAssetsStubPath(), public_path('assets'));

        $this->runLaraBeacon();

        $this->assertPassed(MinificationAnalyzer::class);

        (new Filesystem())->deleteDirectory(public_path('assets'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function passes_with_multiline_assets_emitted_by_vite()
    {
        $this->app->config->set('app.env', 'production');

        $files = new Filesystem();
        $files->ensureDirectoryExists(public_path('build/assets'));
        $files->put(public_path('build/assets/app-hash.js'), str_repeat(str_repeat('const a=1;', 20)."\n", 20));
        $files->put(public_path('build/manifest.json'), json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-hash.js'],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->runLaraBeacon();

            $this->assertPassed(MinificationAnalyzer::class);
        } finally {
            $files->deleteDirectory(public_path('build'));
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function detects_unminified_assets_even_when_listed_in_a_vite_manifest()
    {
        $this->app->config->set('app.env', 'production');

        $files = new Filesystem();
        $files->ensureDirectoryExists(public_path('build/.vite'));
        $files->ensureDirectoryExists(public_path('build/assets'));
        $files->put(public_path('build/assets/app-hash.js'), str_repeat("const value = true;\n", 20));
        $files->put(public_path('build/.vite/manifest.json'), json_encode([
            'resources/js/app.js' => ['file' => 'assets/app-hash.js'],
        ], JSON_THROW_ON_ERROR));

        try {
            $this->runLaraBeacon();

            $this->assertFailed(MinificationAnalyzer::class);
            $this->assertErrorMessageContains(MinificationAnalyzer::class, 'app-hash.js');
        } finally {
            $files->deleteDirectory(public_path('build'));
        }
    }
}
