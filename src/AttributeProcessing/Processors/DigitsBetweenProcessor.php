<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\DigitCountProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DigitsBetweenProcessor extends DigitCountProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $minDigits = $parameters[0] ?? null;
        $maxDigits = $parameters[1] ?? null;

        if (! is_int($minDigits) || ! is_int($maxDigits) || $minDigits < 1 || $maxDigits < 1) {
            return;
        }

        // A reversed count rejects every value, so it writes no contradictory bound.
        if ($minDigits > $maxDigits) {
            $context->descriptions[] = "Note: must have between <code>{$minDigits}</code> and <code>{$maxDigits}</code> digits, which no value can have, so any request that sends this field fails validation.";

            return;
        }

        $this->writeDigitCount($context, $minDigits, $maxDigits);
        $context->descriptions[] = "Must have between <code>{$minDigits}</code> and <code>{$maxDigits}</code> digits.";
    }
}
