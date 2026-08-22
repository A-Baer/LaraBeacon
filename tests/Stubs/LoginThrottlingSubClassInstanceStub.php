<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

class LoginThrottlingSubClassInstanceStub
{
    public function dummyFunction()
    {
        $limiter = new DummyLimiter(cache()->store());

        $limiter->hit('somekey');
    }
}
