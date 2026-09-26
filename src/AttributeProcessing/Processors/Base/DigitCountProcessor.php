<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

abstract class DigitCountProcessor implements AttributeProcessor
{
    /**
     * The largest digit count whose bound an int holds exactly: 10^18 is the
     * lowest 19-digit int, and 10^18 - 1 the highest 18-digit one.
     */
    private const MAX_EXACT_LOWER = 19;

    private const MAX_EXACT_UPPER = 18;

    private const DIGIT_PATTERN = '/^\^\[0-9\]\{\d+(,\d+)?\}\$$/';

    /**
     * Laravel counts the characters of the value's string form, which must be
     * all 0-9. A string may start with zeros ("007" has 3 digits), so it is
     * documented as a digit pattern and a length; a number as the range of
     * whole numbers with that many digits, where 0 has 1.
     */
    protected function writeDigitCount(ParameterContext $context, int $fewest, int $most): void
    {
        if ($context->type === 'string') {
            $this->tighten($context, 'minLength', $fewest, true);
            $this->tighten($context, 'maxLength', $most, false);
            $this->writeDigitPattern($context, $fewest, $most);

            return;
        }

        if (! in_array($context->type, ['integer', 'number'], true)) {
            return;
        }

        if ($fewest <= self::MAX_EXACT_LOWER) {
            $this->tighten($context, 'minimum', $fewest === 1 ? 0 : 10 ** ($fewest - 1), true);
        }

        if ($most <= self::MAX_EXACT_UPPER) {
            $this->tighten($context, 'maximum', 10 ** $most - 1, false);
        }

        // A fraction's string form has a dot, so only a whole number passes.
        if ($context->type === 'number') {
            $context->multipleOf ??= 1;
        }
    }

    /**
     * A string example is generated from the pattern alone, so the pattern
     * carries the tightened length, unless the lengths contradict each other.
     * A pattern from another attribute (Regex) is kept, though the digit
     * pattern still decides accepted values and examples, as Laravel enforces
     * both; a digit pattern written by an earlier digit rule is replaced.
     */
    private function writeDigitPattern(ParameterContext $context, int $fewest, int $most): void
    {
        [$low, $high] = $context->minLength <= $context->maxLength
            ? [$context->minLength, $context->maxLength]
            : [$fewest, $most];

        $pattern = $low === $high ? "^[0-9]{{$low}}$" : "^[0-9]{{$low},{$high}}$";
        $context->valuePatterns[] = $pattern;

        if ($context->pattern === null || preg_match(self::DIGIT_PATTERN, $context->pattern) === 1) {
            $context->pattern = $pattern;
        }
    }

    /**
     * The validator enforces every declared bound, so the published one is the
     * strictest, whatever order the attributes were declared in.
     */
    private function tighten(ParameterContext $context, string $field, int $value, bool $lower): void
    {
        $current = $context->$field;

        $context->$field = match (true) {
            $current === null => $value,
            $lower            => max($current, $value),
            default           => min($current, $value),
        };
    }
}
