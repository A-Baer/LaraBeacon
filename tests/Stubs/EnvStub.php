<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

class EnvStub
{
    public function envCallTest()
    {
        env('This call fails while config is cached');
    }
}
