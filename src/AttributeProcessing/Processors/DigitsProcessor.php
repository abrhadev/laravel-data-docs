<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\DigitCountProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DigitsProcessor extends DigitCountProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $digits = $parameters[0] ?? null;

        if (is_int($digits) && $digits >= 1) {
            $this->writeDigitCount($context, $digits, $digits);
            $context->descriptions[] = "Must have exactly <code>{$digits}</code> digits.";
        }
    }
}
