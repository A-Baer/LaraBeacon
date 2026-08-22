<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

class UndefinedConstantStub
{
    public function foo()
    {
        Foo::DOLOR;
    }
}

