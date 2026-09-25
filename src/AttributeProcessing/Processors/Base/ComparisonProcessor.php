<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

abstract class ComparisonProcessor extends FieldReferenceProcessor
{
    protected function extractValue(mixed $value): string
    {
        return $this->extractFieldName($value);
    }
}
