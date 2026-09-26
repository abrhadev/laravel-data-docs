<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use BackedEnum;
use Illuminate\Contracts\Support\Arrayable;
use ReflectionProperty;
use Spatie\LaravelData\Support\Validation\References\ExternalReference;
use Throwable;
use UnitEnum;

abstract class ValueListProcessor implements AttributeProcessor
{
    /**
     * In and NotIn expose their values only through getRule(ValidationPath),
     * and ArchTest confines ValidationPath to RequirementResolver, so they are
     * read by reflection, through the wrapped Illuminate rule when there is one
     * (In::create, a rule string), and string-formed as Laravel compares them.
     * Null means a value cannot be documented (an ExternalReference).
     *
     * @param  class-string  $attributeClass
     * @param  class-string  $ruleClass
     * @return list<string>|null
     */
    protected function declaredValues(object $attribute, string $attributeClass, string $ruleClass): ?array
    {
        try {
            $values = (new ReflectionProperty($attributeClass, 'values'))->getValue($attribute);

            if (count($values) === 1 && $values[0] instanceof $ruleClass) {
                $values = (new ReflectionProperty($ruleClass, 'values'))->getValue($values[0]);
            }
        } catch (Throwable) {
            return null;
        }

        $strings = [];

        foreach ($this->flatten($values) as $value) {
            $string = $this->stringForm($value);

            if ($string === null) {
                return null;
            }

            $strings[] = $string;
        }

        return array_values(array_unique($strings));
    }

    protected function stringForm(mixed $value): ?string
    {
        return match (true) {
            $value instanceof ExternalReference => null,
            $value instanceof BackedEnum        => (string) $value->value,
            $value instanceof UnitEnum          => $value->name,
            is_bool($value)                     => $value ? '1' : '',
            is_scalar($value), $value === null  => (string) $value,
            default                             => null,
        };
    }

    /**
     * A numeric field is published with numeric values, and Laravel compares
     * the sent value's string form exactly, so a value is kept only when its
     * number prints back as declared: '01' or '1.50' is left out, since 1 or
     * 1.5 sent as published would fail, as is a value no number spells. A
     * boolean compares as '1' (true), '' (false) or the string '0', so only those
     * stay.
     *
     * @param  list<string>  $values
     * @return list<string>
     */
    protected function keepMatchingType(ParameterContext $context, array $values): array
    {
        $type = str_replace('[]', '', $context->type ?? '');

        return array_values(array_filter($values, fn(string $value) => match ($type) {
            'integer' => preg_match('/^-?\d+$/', $value) === 1 && (string) (int) $value === $value,
            'number'  => is_numeric($value) && (string) (float) $value === $value,
            'boolean' => in_array($value, ['1', '0', ''], true),
            default   => true,
        }));
    }

    /**
     * Keeps the enum cases whose string form $keep accepts. An enum with no case
     * left accepts no value at all.
     */
    protected function narrowEnum(ParameterContext $context, callable $keep): void
    {
        $context->keepEnumCases(fn(UnitEnum $case) => $keep($this->stringForm($case)));
    }

    protected function isArrayField(ParameterContext $context): bool
    {
        return str_ends_with($context->type ?? '', '[]');
    }

    /**
     * @return list<mixed>
     */
    private function flatten(array $values): array
    {
        $flat = [];

        foreach ($values as $value) {
            if ($value instanceof Arrayable) {
                $value = $value->toArray();
            }

            array_push($flat, ...(is_array($value) ? $this->flatten($value) : [$value]));
        }

        return $flat;
    }
}
