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

    /**
     * Laravel splits a rule's parameters as CSV, so a reference written 'a,b'
     * names the two fields a and b, as a condition's values are split.
     *
     * @param array<int, mixed> $references
     *
     * @return array<int, string>
     */
    protected function fieldNames(array $references): array
    {
        $names = [];

        foreach ($references as $reference) {
            foreach (str_getcsv($this->extractFieldName($reference), ',', '"', '\\') as $part) {
                $names[] = $part ?? '';
            }
        }

        return $names;
    }

    protected function code(string $value): string
    {
        return "<code>{$value}</code>";
    }

    protected function fieldName(string $name): string
    {
        return "<b><i>{$name}</i></b>";
    }
}
