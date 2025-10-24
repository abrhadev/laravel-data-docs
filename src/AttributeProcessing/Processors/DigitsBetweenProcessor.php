<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DigitsBetweenProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $minDigits = $parameters[0] ?? null;
        $maxDigits = $parameters[1] ?? null;

        if ($minDigits !== null && $maxDigits !== null) {
            $min = (int) ('1' . str_repeat('0', $minDigits - 1));
            $max = (int) str_repeat('9', $maxDigits);
            $context->minimum = $min;
            $context->maximum = $max;
            $context->descriptions[] = "Must have between <code>{$minDigits}</code> and <code>{$maxDigits}</code> digits.";
        }
    }
}
