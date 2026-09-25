<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\VulnerableDependencyAnalyzer;
use BaerSoftware\LaraBeacon\Composer;
use BaerSoftware\LaraBeacon\Tests\Analyzers\AnalyzerTestCase;
use BaerSoftware\LaraBeacon\Tests\Analyzers\Concerns\InteractsWithComposer;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function uses_composers_native_audit_result_when_available()
    {
        $composer = Mockery::mock(Composer::class);
        $composer->shouldReceive('audit')->once()->andReturn([
            'advisories' => [
                'example/package' => [[
                    'advisoryId' => 'PKSA-example',
                    'title' => 'A current Packagist advisory',
                ]],
            ],
        ]);
        $composer->shouldReceive('getLockFile')->once()->andReturn(null);
        $this->app->instance(Composer::class, $composer);

        $this->runLaraBeacon();

        $this->assertFailed(VulnerableDependencyAnalyzer::class);
        $this->assertErrorMessageContains(VulnerableDependencyAnalyzer::class, 'example/package');
        $this->assertErrorMessageContains(VulnerableDependencyAnalyzer::class, 'A current Packagist advisory');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function composer_audit_uses_the_locked_json_result()
    {
        $composer = new class(new Filesystem, __DIR__) extends Composer {
            public $options;

            public $includeErrorOutput;

            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                $this->options = $options;
                $this->includeErrorOutput = $includeErrorOutput;

                return '{"advisories":[],"abandoned":[]}';
            }
        };

        $this->assertSame(['advisories' => [], 'abandoned' => []], $composer->audit());
        $this->assertSame(['audit', '--locked', '--format=json'], $composer->options);
        $this->assertFalse($composer->includeErrorOutput);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidComposerAuditOutputs')]
    public function rejects_invalid_composer_audit_output(string $output)
    {
        $composer = new class(new Filesystem, __DIR__) extends Composer {
            public $output;

            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                return $this->output;
            }
        };
        $composer->output = $output;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('valid JSON security audit result');

        $composer->audit();
    }

    public static function invalidComposerAuditOutputs(): iterable
    {
        yield 'invalid JSON' => ['Composer audit failed'];
        yield 'null advisories' => ['{"advisories":null}'];
        yield 'false advisories' => ['{"advisories":false}'];
        yield 'string advisories' => ['{"advisories":"invalid"}'];
    }
}
