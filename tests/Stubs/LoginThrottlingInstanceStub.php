<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

use Illuminate\Cache\RateLimiter;

class LoginThrottlingInstanceStub
{
    public function dummyFunction()
    {
        $limiter = new RateLimiter(cache()->store());

        $limiter->hit('somekey');
    }
}
