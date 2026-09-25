<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

/**
 * Descriptions name two kinds of token and render them differently: a value the
 * consumer sends is monospaced, a field they send it under is bold italic. A
 * sentence such as "Required when account_type is business" is otherwise two
 * identical-looking tokens whose roles the reader has to infer from grammar.
 */
abstract class FieldReferenceProcessor implements AttributeProcessor
{
    protected function extractFieldName(mixed $value): string
    {
        if ($value instanceof FieldReference) {
            return $value->name;
        }

        return (string) $value;
    }

    protected function code(string $value): string
    {
        return "<code>{$value}</code>";
    }

    protected function fieldName(string $name): string
    {
        return "<b><i>{$name}</i></b>";
    }

    protected function operand(mixed $value): string
    {
        return $value instanceof FieldReference
            ? $this->fieldName($value->name)
            : $this->code((string) $value);
    }
}
