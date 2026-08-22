<?php

namespace BaerSoftware\LaraBeacon\PHPStan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use PhpParser\Node;
use PhpParser\Node\PropertyItem;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class FillableForeignKeyModelRule implements Rule
{
    use AnalyzesNodes;

    /**
     * @return string
     */
    public function getNodeType(): string
    {
        return PropertyItem::class;
    }

    /**
     * @param Node $node
     * @param Scope $scope
     * @return string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->name instanceof Node\Identifier
            || $node->name->toString() !== 'fillable') {
            // We are only looking for the fillable property declaration.
            return [];
        }

        if (! is_subclass_of($modelClass = $scope->getClassReflection()->getName(), Model::class)) {
            // We are only looking for Model classes.
            return [];
        }

        if (! $node->default instanceof Node\Expr\Array_) {
            return [];
        }

        foreach ($node->default->items as $item) {
            if (is_null($item)) {
                continue;
            }

            if ($item->value instanceof Node\Scalar\String_
                && Str::contains($key = $item->value->value, '_id', true)) {
                return [
                    RuleErrorBuilder::message(sprintf(
                        'Potential foreign key %s declared as fillable and available for mass assignment.',
                        $key
                    ))->identifier('larabeacon.fillableForeignKey')->build(),
                ];
            }
        }

        return [];
    }
}
