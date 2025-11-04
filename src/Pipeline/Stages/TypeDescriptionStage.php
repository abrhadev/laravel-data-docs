<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;

final class TypeDescriptionStage implements ParameterPipelineStage
{
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
        if ($context->enumInfo) {
            $enumDesc = $this->getEnumDescription($context);
            $context->description = $this->prependDescription($enumDesc, $context->description);

            return $context;
        }

        if ($context->type && isset(self::TYPE_DESCRIPTIONS[$context->type])) {
            $typeDesc = self::TYPE_DESCRIPTIONS[$context->type];
            $context->description = $this->prependDescription($typeDesc, $context->description);
        }

        return $context;
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
                EnumType::PURE => "<code>{$case->name}</code>",
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
