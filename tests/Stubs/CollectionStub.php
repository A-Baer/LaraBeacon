<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

use BaerSoftware\LaraBeacon\Tests\Stubs\Models\BananaModel;

class CollectionStub
{
    public function countTest()
    {
        return BananaModel::all()->count();
    }

    public function firstTest()
    {
        return BananaModel::all()->first();
    }
}
