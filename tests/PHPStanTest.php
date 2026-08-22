<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\PHPStan;
use Composer\InstalledVersions;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class PHPStanTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_decodes_json_after_phpstan_instructions()
    {
        $phpStan = new class(new Filesystem()) extends PHPStan {
            public function decode(string $output): ?array
            {
                return $this->decodeResult($output);
            }
        };

        $result = $phpStan->decode("Instructions for interpreting errors.\n".json_encode([
            'totals' => ['errors' => 0, 'file_errors' => 1],
            'files' => ['/app/Test.php' => ['messages' => []]],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame(1, $result['totals']['file_errors']);
        $this->assertArrayHasKey('/app/Test.php', $result['files']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_decodes_json_before_phpstan_instructions()
    {
        $phpStan = new class(new Filesystem()) extends PHPStan {
            public function decode(string $output): ?array
            {
                return $this->decodeResult($output);
            }
        };

        $result = $phpStan->decode(json_encode([
            'totals' => ['errors' => 0, 'file_errors' => 2],
            'files' => [],
        ], JSON_THROW_ON_ERROR).'Instructions for interpreting errors.');

        $this->assertSame(2, $result['totals']['file_errors']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function runtime_configuration_references_host_dependencies()
    {
        $rootPath = realpath(__DIR__.'/..');
        $phpStan = new class(new Filesystem(), $rootPath) extends PHPStan {
            public function runtimeConfig(): string
            {
                return $this->createRuntimeConfig();
            }
        };

        $path = $phpStan->runtimeConfig();

        try {
            $config = (new Filesystem())->get($path);

            $this->assertStringContainsString(
                json_encode(InstalledVersions::getInstallPath('larastan/larastan').'/extension.neon', JSON_THROW_ON_ERROR),
                $config
            );
            $this->assertStringContainsString(
                json_encode(realpath(__DIR__.'/../phpstan.neon'), JSON_THROW_ON_ERROR),
                $config
            );
            $this->assertStringContainsString('tmpDir:', $config);
        } finally {
            (new Filesystem())->delete($path);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function phpstan_binary_uses_the_composer_install_path()
    {
        $phpStan = new class(new Filesystem(), realpath(__DIR__.'/..')) extends PHPStan {
            public function command(): array
            {
                return $this->findPHPStan();
            }
        };

        $this->assertSame([
            PHP_BINARY,
            InstalledVersions::getInstallPath('phpstan/phpstan').'/phpstan.phar',
        ], $phpStan->command());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_reports_invalid_phpstan_output()
    {
        $phpStan = new class(new Filesystem(), realpath(__DIR__.'/..')) extends PHPStan {
            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                return 'Unable to load PHPStan configuration.';
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to load PHPStan configuration.');

        $phpStan->start([]);
    }
}
