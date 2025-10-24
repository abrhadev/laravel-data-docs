<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DigitsProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $digits = $parameters[0] ?? null;

        if ($digits !== null) {
            $min = (int) ('1' . str_repeat('0', $digits - 1));
            $max = (int) str_repeat('9', $digits);
            $context->minimum = $min;
            $context->maximum = $max;
            $context->descriptions[] = "Must have exactly <code>{$digits}</code> digits.";
        }
    }
}
