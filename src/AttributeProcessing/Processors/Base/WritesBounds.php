<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

trait WritesBounds
{
    /**
     * The length, item-count and multipleOf fields are ints. A float is written
     * there only when an int holds it exactly; truncating 0.5 to 0 would publish
     * a wrong bound.
     */
    protected function asInteger(int|float $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (floor($value) !== $value || $value < PHP_INT_MIN || $value >= PHP_INT_MAX) {
            return null;
        }

        return (int) $value;
    }

    /**
     * A length or count is a whole number, so min:2.5 means at least 3 and max:2.5
     * at most 2. A negative bound constrains nothing or is invalid OpenAPI.
     */
    protected function wholeBound(int|float $value, bool $lower): ?int
    {
        $whole = $this->asInteger($lower ? ceil($value) : floor($value));

        return $whole !== null && $whole >= 0 ? $whole : null;
    }

    /**
     * A numeric bound field holds a float exactly, so a fractional bound is
     * published as declared and an integral one as an int.
     */
    protected function recordBound(ParameterContext $context, string $field, int|float $value, bool $lower): void
    {
        if (is_finite($value)) {
            $this->tighten($context, $field, $this->asInteger($value) ?? $value, $lower);
        }
    }

    /**
     * The validator enforces every declared bound, so the published one is the
     * strictest, whatever order the attributes were declared in.
     */
    protected function tighten(ParameterContext $context, string $field, int|float $value, bool $lower): void
    {
        $current = $context->$field;

        $context->$field = match (true) {
            $current === null => $value,
            $lower            => max($current, $value),
            default           => min($current, $value),
        };
    }
}
