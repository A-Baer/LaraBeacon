<?php

namespace BaerSoftware\LaraBeacon\Tests\Stubs;

use BaerSoftware\LaraBeacon\Tests\Stubs\Models\BananaModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MissingRelationStub
{
    public function existingRelationTest()
    {
        AppleModel::query()->whereHas('bananas')->get();
        AppleModel::query()->whereDoesntHave('bananas')->get();
    }

    public function missingRelationTest()
    {
        AppleModel::query()->whereHas('pineapples')->get();
        AppleModel::query()->whereDoesntHave('pineapples')->get();
    }
}

class AppleModel extends Model
{
    public function bananas(): HasMany
    {
        return $this->hasMany(BananaModel::class);
    }
}
