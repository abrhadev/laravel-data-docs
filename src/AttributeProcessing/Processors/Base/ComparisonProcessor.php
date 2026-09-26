<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

abstract class ComparisonProcessor extends FieldReferenceProcessor
{
    use WritesBounds;

    private const KINDS = [
        'gt'  => ['field' => 'exclusiveMinimum', 'lower' => true, 'relation' => 'greater than', 'items' => 'more items than'],
        'gte' => ['field' => 'minimum', 'lower' => true, 'relation' => 'greater than or equal to', 'items' => 'at least as many items as'],
        'lt'  => ['field' => 'exclusiveMaximum', 'lower' => false, 'relation' => 'less than', 'items' => 'fewer items than'],
        'lte' => ['field' => 'maximum', 'lower' => false, 'relation' => 'less than or equal to', 'items' => 'at most as many items as'],
    ];

    /**
     * Laravel compares a literal bound as a number: a string field needs a numeric
     * string, and no array ever passes. Only a field reference compares string
     * length or item count, so the sentence and the schema follow the field type.
     * On a string field the bound is kept on the context for the example only.
     * INF or NAN reaches the rule as a string that is not numeric, which Laravel
     * reads as a field name, so it is not documented.
     */
    protected function compare(ParameterContext $context, mixed $value, string $kind): void
    {
        if (is_float($value) && ! is_finite($value)) {
            return;
        }

        ['field' => $field, 'lower' => $lower, 'relation' => $relation, 'items' => $items] = self::KINDS[$kind];
        // Laravel reads the first field of a reference written 'a,b'.
        $operand = $value instanceof FieldReference ? $this->fieldName($this->fieldNames([$value])[0]) : $this->code((string) $value);
        $literal = is_int($value) || is_float($value);

        if ($context->type === 'string') {
            if ($literal) {
                $context->numericString = true;
                $this->applyBound($context, $field, $value, $lower);
            }

            $context->descriptions[] = $literal
                ? "Must be a number {$relation} {$operand}."
                : "Must be {$relation} {$operand}, compared as numbers when both values are numeric and by length otherwise.";

            return;
        }

        if (str_ends_with($context->type ?? '', '[]')) {
            $context->descriptions[] = $literal
                ? "Note: must be {$relation} {$operand}, which no array can be, so any request that sends this field fails validation."
                : "Must have {$items} {$operand}.";

            return;
        }

        $this->applyBound($context, $field, $value, $lower);
        $context->descriptions[] = "Must be {$relation} {$operand}.";
    }

    /**
     * A field reference writes nothing, so it never clears or loosens a bound
     * another attribute already set; a literal is recorded exactly.
     */
    protected function applyBound(ParameterContext $context, string $field, mixed $value, bool $lower): void
    {
        if (! is_int($value) && ! is_float($value)) {
            return;
        }

        $this->recordBound($context, $field, $value, $lower);
    }
}
