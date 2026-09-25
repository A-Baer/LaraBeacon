<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers\Security;

use BaerSoftware\LaraBeacon\Analyzers\Security\FrontendVulnerableDependencyAnalyzer;
use BaerSoftware\LaraBeacon\NPM;
use BaerSoftware\LaraBeacon\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

class FrontendVulnerableDependencyAnalyzerTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function severity_and_message_reflect_the_audit_breakdown()
    {
        $npm = new class(new Filesystem(), __DIR__) extends NPM {
            public function vulnerabilitySummary($excludeDev = true)
            {
                return ['moderate' => 1, 'high' => 2];
            }
        };

        $analyzer = new FrontendVulnerableDependencyAnalyzer($npm);
        $analyzer->handle();

        $this->assertSame($analyzer::SEVERITY_MAJOR, $analyzer->severity);
        $this->assertStringContainsString('moderate: 1, high: 2', $analyzer->errorMessage());
        $this->assertStringContainsString('npm, pnpm or Yarn', $analyzer->errorMessage());
        $this->assertStringContainsString('review reachability and deployment impact', $analyzer->errorMessage());
    }
}
