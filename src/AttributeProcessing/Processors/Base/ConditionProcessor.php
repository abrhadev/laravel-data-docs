<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
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
     * Laravel splits a condition's rule string as CSV, so a field written 'a,b'
     * reads the field a, and b becomes the first compared value.
     *
     * @return array{0: string, 1: list<string>}
     */
    protected function conditionParts(mixed $field): array
    {
        $names = $this->fieldNames([$field]);

        return [(string) array_shift($names), $names];
    }

    /**
     * Spatie joins a condition's values into the rule string with commas, and
     * Laravel splits them back as CSV, so 'DE,AT' is two values.
     *
     * @param array<int, mixed> $values
     */
    protected function renderValues(array $values): ?string
    {
        $split = [];

        foreach ($values as $value) {
            array_push($split, ...(is_string($value)
                ? array_map(fn(?string $part) => $part ?? '', str_getcsv($value, ',', '"', '\\'))
                : [$value]));
        }

        $rendered = [];

        foreach ($split as $value) {
            if ($value instanceof ExternalReference) {
                return null;
            }

            $rendered[] = match (true) {
                $value instanceof BackedEnum => $this->code($value->name) . " ({$value->value})",
                $value instanceof UnitEnum   => $this->code($value->name),
                // Spatie writes a PHP null as an empty part, which Laravel compares as ''.
                $value === null, $value === '' => $this->code('""'),
                is_bool($value)                => $this->code($value ? 'true' : 'false'),
                default                        => $this->code((string) $value),
            };
        }

        if (count($rendered) === 1) {
            return $rendered[0];
        }

        return 'one of: ' . implode(', ', $rendered);
    }

    /**
     * The validation attributes declared after this one, in the order upstream
     * walks them. An attribute not declared on the property is treated as
     * declared first.
     *
     * @return array<int, object>
     */
    protected function declaredAfter(object $attribute, ParameterContext $context): array
    {
        return ReplacedRules::declaredAfter($attribute, $context->property);
    }

    /**
     * The rules upstream adds for a declaration: a #[Rule] is expanded the
     * way AttributesRuleInferrer expands it, and one that cannot be expanded
     * counts as nothing.
     *
     * @return array<int, object>
     */
    protected function expand(object $candidate): array
    {
        return ReplacedRules::expand($candidate);
    }
}
