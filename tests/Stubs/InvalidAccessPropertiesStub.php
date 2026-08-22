<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

class InvalidAccessPropertiesStub
{
    private $foo;
    protected $bar;
    public $ipsum;
}

class BarAccessProperties extends InvalidAccessPropertiesStub
{
    public function foo()
    {
        $this->loremipsum; // nonexistent
        $this->foo; // private from an ancestor
    }
}
