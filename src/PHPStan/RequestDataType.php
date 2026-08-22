<?php

declare(strict_types=1);

namespace BaerSoftware\LaraBeacon\PHPStan;

use PHPStan\Type\CompoundType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

class RequestDataType extends StringType
{
    /**
     * @param mixed[] $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }

    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return IsSuperTypeOfResult::createYes();
        }
        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return IsSuperTypeOfResult::createNo();
    }
}
