<?php

declare(strict_types=1);

namespace BaerSoftware\LaraBeacon\PHPStan;

use Illuminate\Http\Request;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

class RequestMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private const ITEM_METHODS = ['get', 'input', 'query', 'post', 'old', 'cookie', 'header'];

    private const UNSAFE_ARRAY_METHODS = ['all', 'except'];

    public function getClass(): string
    {
        return Request::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return in_array(
            $methodReflection->getName(),
            array_merge(self::ITEM_METHODS, self::UNSAFE_ARRAY_METHODS, ['only']),
            true
        );
    }

    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        if (in_array($methodReflection->getName(), self::UNSAFE_ARRAY_METHODS, true)) {
            return new RequestArrayDataType(
                new UnionType([new StringType(), new IntegerType()]),
                new RequestDataType()
            );
        }

        if ($methodReflection->getName() === 'only') {
            return new ArrayType(new StringType(), new RequestDataType());
        }

        return TypeCombinator::union(
            new RequestDataType(),
            new ArrayType(new IntegerType(), new RequestDataType()),
            new NullType()
        );
    }
}
