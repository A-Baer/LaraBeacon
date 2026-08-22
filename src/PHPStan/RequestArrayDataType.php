<?php

declare(strict_types=1);

namespace BaerSoftware\LaraBeacon\PHPStan;

use PHPStan\Type\ArrayType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;

class RequestArrayDataType extends ArrayType
{
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

    /**
     * @param mixed[] $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['keyType'], $properties['itemType']);
    }
}
