<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class MultipleOfProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if (!is_int($value) && !(is_float($value) && is_finite($value))) {
            return;
        }

        // multipleOf is an int field and OpenAPI requires it to be positive, so a
        // fractional or non-positive divisor is stated in the description only.
        $divisor = match (true) {
            is_int($value)                                                  => $value,
            $value >= 1 && $value < PHP_INT_MAX && floor($value) === $value => (int) $value,
            default                                                         => null,
        };

        if ($divisor !== null && $divisor > 0) {
            $context->multipleOf = $divisor;
        } elseif ($value != 0) {
            $context->exampleMultipleOf = abs($value);
        }

        // multiple_of fails any value that is not numeric, so a string field takes a numeric string.
        if ($context->type === 'string') {
            $context->numericString = true;
        }

        // Laravel fails multiple_of:0 for every value, 0 included.
        $context->descriptions[] = match (true) {
            $value == 0                 => 'Note: must be a multiple of 0, which no value can be, so any request that sends this field fails validation.',
            $context->type === 'string' => "Must be a number that is a multiple of {$value}.",
            default                     => "Must be a multiple of {$value}.",
        };
    }
}
