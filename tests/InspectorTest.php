<?php

namespace BaerSoftware\LaraBeacon\Tests;

use BaerSoftware\LaraBeacon\Inspection\Inspector;
use BaerSoftware\LaraBeacon\Inspection\QueryBuilder;

class InspectorTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function preserves_the_original_inspect_signature_for_subclasses()
    {
        $inspector = new LegacyInspector;

        $this->assertSame([], $inspector->inspect(new QueryBuilder));
    }
}

class LegacyInspector extends Inspector
{
    public function inspect(QueryBuilder $builder)
    {
        return parent::inspect($builder);
    }
}
