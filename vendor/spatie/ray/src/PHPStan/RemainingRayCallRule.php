<?php

namespace Spatie\Ray\PHPStan;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class RemainingRayCallRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Node\Expr\FuncCall) {
            return [];
        }

        if ($node->name->parts[0] !== 'ray') {
            return [];
        }

        return [
            RuleErrorBuilder::message('Remaining ray call in application code')
                ->build(),
        ];
    }
}
