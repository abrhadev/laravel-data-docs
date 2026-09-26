<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;

final class TypeDescriptionStage implements ParameterPipelineStage
{
    private const EMPTY_ALLOWED_NOTE = 'Note: the list of allowed values is empty, so any request that sends this field fails validation.';

    private const TYPE_DESCRIPTIONS = [
        'boolean'   => 'Must be a boolean.',
        'integer'   => 'Must be an integer.',
        'number'    => 'Must be a number.',
        'string'    => 'Must be a string.',
        'object'    => 'Must be an object.',
        'object[]'  => 'Must be an array of objects.',
        'string[]'  => 'Must be an array of strings.',
        'integer[]' => 'Must be an array of integers.',
        'boolean[]' => 'Must be an array of booleans.',
        'number[]'  => 'Must be an array of numbers.',
    ];

    public function process(ParameterContext $context): ParameterContext
    {
        $lead = match (true) {
            $context->enumInfo !== null                                      => $this->getEnumDescription($context),
            $context->type && isset(self::TYPE_DESCRIPTIONS[$context->type]) => self::TYPE_DESCRIPTIONS[$context->type],
            default                                                          => '',
        };

        $sentences = implode(' ', array_filter([$lead, ...$this->valueSentences($context)]));

        if ($sentences !== '') {
            $context->description = $this->prependDescription($sentences, $context->description);
        }

        return $context;
    }

    /**
     * In and NotIn may be declared in either order, so their sentences are
     * written here, once both have been applied.
     *
     * @return list<string>
     */
    private function valueSentences(ParameterContext $context): array
    {
        $isArray = str_ends_with($context->type ?? '', '[]');
        $sentences = [];

        $allowed = $context->acceptedAllowedValues();

        if ($context->enumInfo === null && $allowed !== null) {
            $sentences[] = match (true) {
                $allowed === [] => self::EMPTY_ALLOWED_NOTE,
                $isArray        => 'Each item must be one of: ' . $this->valueList($context, $allowed) . '.',
                default         => 'Must be one of: ' . $this->valueList($context, $allowed) . '.',
            };
        }

        $unreconciled = $isArray || ($context->enumInfo === null && $context->allowedValues === null);

        if ($context->excludedValues && $unreconciled) {
            $sentences[] = $isArray
                ? 'Must include at least one item that is not one of: ' . $this->valueList($context, $context->excludedValues) . '.'
                : 'Must not be one of: ' . $this->valueList($context, $context->excludedValues) . '.';
        }

        return $sentences;
    }

    /**
     * Laravel compares a boolean true as '1' and false as ''; a '0' matches only
     * 0 or "0", not false.
     *
     * @param  list<string>  $values
     */
    private function valueList(ParameterContext $context, array $values): string
    {
        $boolean = str_replace('[]', '', $context->type ?? '') === 'boolean';

        return implode(', ', array_map(
            fn(string $value) => '<code>' . ($boolean ? match ($value) {
                '1' => 'true', '' => 'false', default => "\"{$value}\""
            } : $value) . '</code>',
            $values
        ));
    }

    private function prependDescription(string $typeDescription, string $existingDescription): string
    {
        if ($existingDescription === '') {
            return $typeDescription;
        }

        return $typeDescription . ' ' . $existingDescription;
    }

    private function getEnumDescription(ParameterContext $context): string
    {
        $enumDescriptions = array_map(
            fn($case) => match ($context->enumInfo->enumType) {
                EnumType::PURE                                => "<code>{$case->name}</code>",
                EnumType::STRING_BACKED, EnumType::INT_BACKED => "<code>{$case->name}</code> ({$case->value})",
            },
            $context->enumInfo->cases
        );

        $isArray = str_ends_with($context->type ?? '', '[]');

        if ($isArray) {
            return 'Must be an array of enums. Each item must be one of: ' . implode(', ', $enumDescriptions) . '.';
        }

        return 'Must be one of: ' . implode(', ', $enumDescriptions) . '.';
    }
}
