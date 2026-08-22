<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

use Illuminate\Cache\RateLimiter;

class LoginThrottlingAnonymousInstanceStub
{
    public function dummyFunction()
    {
        (new RateLimiter(cache()->store()))->hit('somekey');
    }
}
