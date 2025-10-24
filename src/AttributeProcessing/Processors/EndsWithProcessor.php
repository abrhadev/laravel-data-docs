<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class EndsWithProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();

        if (!empty($parameters)) {
            $flatParameters = is_array($parameters[0]) ? $parameters[0] : $parameters;
            $escapedValues = array_map(fn($arg) => preg_quote($arg, '/'), $flatParameters);
            $context->pattern = '(' . implode('|', $escapedValues) . ')$';
            $formattedValues = array_map(fn($arg) => "<code>$arg</code>", $flatParameters);
            $context->descriptions[] = 'Must end with one of: ' . implode(', ', $formattedValues) . '.';
        }
    }
}
