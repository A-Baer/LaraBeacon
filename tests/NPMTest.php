<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\NPM;
use Illuminate\Filesystem\Filesystem;

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
}
