<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use BackedEnum;
use Spatie\LaravelData\Support\Validation\References\ExternalReference;
use Throwable;
use UnitEnum;

abstract class ConditionProcessor extends FieldReferenceProcessor
{
    /**
     * RequiredWith, RequiredWithAll, RequiredWithout and RequiredWithoutAll
     * declare $fields with no default and fill it only inside a loop, so a
     * declaration carrying no fields leaves it uninitialised and parameters()
     * throws. A documentation build must not fail over one attribute.
     * Prohibits shares the same defect.
     *
     * @return array<int, mixed>|null
     */
    protected function parametersOf(object $attribute): ?array
    {
        try {
            return $attribute->parameters();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param array<int, mixed> $references
     *
     * @return array<int, string>
     */
    protected function fieldNames(array $references): array
    {
        return array_map(fn($reference) => $this->extractFieldName($reference), $references);
    }

    /**
     * @param array<int, mixed> $values
     */
    protected function renderValues(array $values): ?string
    {
        if ($values === []) {
            return null;
        }

        $rendered = [];

        foreach ($values as $value) {
            if ($value instanceof ExternalReference) {
                return null;
            }

            $rendered[] = match (true) {
                $value instanceof BackedEnum => $this->code($value->name) . " ({$value->value})",
                $value instanceof UnitEnum   => $this->code($value->name),
                $value === null              => $this->code('null'),
                is_bool($value)              => $this->code($value ? 'true' : 'false'),
                default                      => $this->code((string) $value),
            };
        }

        if (count($rendered) === 1) {
            return $rendered[0];
        }

        return 'one of: ' . implode(', ', $rendered);
    }
}
