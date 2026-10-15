<?php

namespace BaerSoftware\LaraBeacon\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

class BoostResourcesTest extends BaseTestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function ships_laravel_boost_guidelines_and_skill()
    {
        $root = dirname(__DIR__).'/resources/boost';

        $this->assertFileExists($root.'/guidelines/core.blade.php');
        $this->assertStringContainsString('php artisan larabeacon', file_get_contents($root.'/guidelines/core.blade.php'));

        $skill = file_get_contents($root.'/skills/larabeacon-release-check/SKILL.md');

        $this->assertMatchesRegularExpression('/\A---\nname: larabeacon-release-check\ndescription: .+\n---\n/', $skill);
    }
}
