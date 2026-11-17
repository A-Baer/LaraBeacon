<?php

namespace BaerSoftware\LaraBeacon\Tests\Analyzers;

use BaerSoftware\LaraBeacon\Analyzers\Analyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\FillableForeignKeyAnalyzer;
use BaerSoftware\LaraBeacon\Analyzers\Security\HSTSHeaderAnalyzer;
use BaerSoftware\LaraBeacon\Tests\TestCase;

class DocsUrlTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function documentation_links_point_to_the_check_pages()
    {
        $this->assertSame('https://larabeacon.com/checks/fillable-foreign-key', $this->app->make(FillableForeignKeyAnalyzer::class)->getDocsUrl());
        $this->assertSame('https://larabeacon.com/checks/hsts-header', $this->app->make(HSTSHeaderAnalyzer::class)->getDocsUrl());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function slugs_follow_the_catalogue_rules()
    {
        $this->assertSame('telescope-n-plus-one-query', Analyzer::docsSlug('TelescopeNPlusOneQueryAnalyzer'));
        $this->assertSame('http-two', Analyzer::docsSlug('HttpTwoAnalyzer'));
        $this->assertSame('php-ini', Analyzer::docsSlug('PHPIniAnalyzer'));
    }
}
