<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

abstract class ComparisonProcessor implements AttributeProcessor
{
    protected function extractValue(mixed $value): string
    {
        if ($value instanceof FieldReference) {
            return $value->name;
        }

        return (string) $value;
    }
}
