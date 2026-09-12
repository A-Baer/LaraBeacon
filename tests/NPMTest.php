<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\NPM;
use Illuminate\Filesystem\Filesystem;
use RuntimeException;

class NPMTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function production_audits_use_the_supported_omit_dev_option()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public $options;

            public $includeErrorOutput;

            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                $this->options = $options;
                $this->includeErrorOutput = $includeErrorOutput;

                return '{"metadata":{"vulnerabilities":{"high":1,"total":1}}}';
            }
        };

        $this->assertSame(1, $npm->countVulnerabilities());
        $this->assertSame(['audit', '--omit=dev', '--json'], $npm->options);
        $this->assertFalse($npm->includeErrorOutput);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function yarn_classic_audit_summaries_are_counted()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public $options;

            public function findNpmOrYarn()
            {
                $this->isYarn = true;

                return ['yarn'];
            }

            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                $this->options = $options;

                return implode("\n", [
                    json_encode(['type' => 'auditAdvisory', 'data' => ['advisory' => ['severity' => 'high']]]),
                    json_encode(['type' => 'auditSummary', 'data' => [
                        'vulnerabilities' => ['info' => 0, 'low' => 0, 'moderate' => 0, 'high' => 1, 'critical' => 0],
                    ]]),
                ]);
            }
        };

        $this->assertSame(1, $npm->countVulnerabilities());
        $this->assertSame(['audit', '--json'], $npm->options);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function exposes_vulnerability_counts_by_severity()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                return '{"metadata":{"vulnerabilities":{"info":2,"low":1,"moderate":3,"high":2,"critical":1,"total":9}}}';
            }
        };

        $this->assertSame([
            'low' => 1,
            'moderate' => 3,
            'high' => 2,
            'critical' => 1,
        ], $npm->vulnerabilitySummary());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function audit_error_objects_are_not_reported_as_zero_vulnerabilities()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                return '{"error":{"code":"ENOLOCK","summary":"A lockfile is required"}}';
            }
        };

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('valid vulnerability audit summary');

        $npm->vulnerabilitySummary();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_audit_json_is_not_reported_as_zero_vulnerabilities()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                return 'registry connection failed';
            }
        };

        $this->expectException(RuntimeException::class);

        $npm->vulnerabilitySummary();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function malformed_nested_audit_summaries_are_not_reported_as_zero_vulnerabilities()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public function runCommand(array $options = [], $includeErrorOutput = true)
            {
                return '{"metadata":{"vulnerabilities":{"error":"registry unavailable"}}}';
            }
        };

        $this->expectException(RuntimeException::class);

        $npm->vulnerabilitySummary();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function a_yarn_lockfile_takes_precedence_when_npm_is_also_installed()
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'larabeacon-npm-test-'.uniqid();
        $files->makeDirectory($root);
        $files->put($root.'/package.json', '{}');
        $files->put($root.'/yarn.lock', '');

        try {
            $npm = new class($files, $root) extends NPM {
                protected function commandExists(string $command)
                {
                    return in_array($command, ['npm', 'yarn'], true);
                }
            };

            $this->assertSame(['yarn'], $npm->findNpmOrYarn());
        } finally {
            $files->deleteDirectory($root);
        }
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function an_explicit_npm_package_manager_takes_precedence_over_a_stale_yarn_lockfile()
    {
        $files = new Filesystem;
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'larabeacon-npm-test-'.uniqid();
        $files->makeDirectory($root);
        $files->put($root.'/package.json', '{"packageManager":"npm@11.0.0"}');
        $files->put($root.'/yarn.lock', '');

        try {
            $npm = new class($files, $root) extends NPM {
                protected function commandExists(string $command)
                {
                    return in_array($command, ['npm', 'yarn'], true);
                }
            };

            $this->assertSame(['npm'], $npm->findNpmOrYarn());
        } finally {
            $files->deleteDirectory($root);
        }
    }
}
