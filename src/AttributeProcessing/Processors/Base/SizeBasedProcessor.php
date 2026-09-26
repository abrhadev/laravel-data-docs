<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

abstract class SizeBasedProcessor implements AttributeProcessor
{
    use WritesBounds;

    /**
     * Spatie also accepts an ExternalReference, which is resolved from the request
     * at validation time and is never dereferenced here. INF and NAN are floats
     * but bound nothing a document can state.
     */
    protected function isLiteral(mixed $value): bool
    {
        return is_int($value) || (is_float($value) && is_finite($value));
    }

    /**
     * A length, an item count and an integer are whole numbers, so a range with
     * no whole number in it (size:2.5, between:2.2,2.8, between:5,2) never passes.
     * A length and an item count are never negative either (size:-1). A number
     * accepts fractions, so only a reversed range fails it.
     */
    protected function rangeIsEmpty(ParameterContext $context, int|float $low, int|float $high): bool
    {
        return match ($this->impossibleSubject($context)) {
            null              => false,
            'number'          => $low > $high,
            'string', 'array' => ceil($low) > floor($high) || floor($high) < 0,
            default           => ceil($low) > floor($high),
        };
    }

    /**
     * Spatie gives an int property Laravel's numeric rule, not integer, so a
     * fractional value can still pass a range with no integer in it, and is
     * then truncated; only a reversed range fails every request.
     */
    protected function impossibleOutcome(ParameterContext $context, int|float $low, int|float $high): string
    {
        return $this->impossibleSubject($context) === 'integer' && $low <= $high
            ? 'so any integer sent fails validation'
            : 'so any request that sends this field fails validation';
    }

    protected function impossibleSubject(ParameterContext $context): ?string
    {
        return match (true) {
            $context->type === 'string'               => 'string',
            $context->type === 'integer'              => 'integer',
            $context->type === 'number'               => 'number',
            str_ends_with($context->type ?? '', '[]') => 'array',
            default                                   => null,
        };
    }

    protected function getUnit(ParameterContext $context, int|float $value): string
    {
        return match (true) {
            $context->type === 'string'                     => $value == 1 ? 'character' : 'characters',
            str_ends_with($context->type ?? '', '[]')       => $value == 1 ? 'item' : 'items',
            in_array($context->type, ['integer', 'number']) => '',
            default                                         => 'characters',
        };
    }

    protected function applyConstraint(ParameterContext $context, string $property, int|float $value): void
    {
        $lower = $property === 'min';

        if (in_array($context->type, ['integer', 'number'])) {
            $this->recordBound($context, $lower ? 'minimum' : 'maximum', $value, $lower);

            return;
        }

        $value = $this->wholeBound($value, $lower);

        if ($value === null) {
            return;
        }

        if ($context->type === 'string') {
            $this->tighten($context, $property . 'Length', $value, $lower);
        } elseif (str_ends_with($context->type ?? '', '[]')) {
            $this->tighten($context, $property . 'Items', $value, $lower);
        }
    }
}
