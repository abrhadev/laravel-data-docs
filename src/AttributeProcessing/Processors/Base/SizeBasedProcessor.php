<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

abstract class SizeBasedProcessor implements AttributeProcessor
{
    protected function getUnit(ParameterContext $context, int $value): string
    {
        return match (true) {
            $context->type === 'string'                     => $value === 1 ? 'character' : 'characters',
            str_ends_with($context->type ?? '', '[]')       => $value === 1 ? 'item' : 'items',
            in_array($context->type, ['integer', 'number']) => '',
            default                                         => 'characters',
        };
    }

    protected function applyConstraint(ParameterContext $context, string $property, int $value): void
    {
        if ($context->type === 'string') {
            $propertyName = $property . 'Length';
            $context->$propertyName = $value;
        } elseif (str_ends_with($context->type ?? '', '[]')) {
            $propertyName = $property . 'Items';
            $context->$propertyName = $value;
        } elseif (in_array($context->type, ['integer', 'number'])) {
            $propertyName = $property === 'min' ? 'minimum' : 'maximum';
            $context->$propertyName = $value;
        }
    }
}
