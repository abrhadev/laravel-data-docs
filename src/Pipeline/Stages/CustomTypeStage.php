<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessorRegistry;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\ValueObjects\CustomTypeConfig;

final class CustomTypeStage implements ParameterPipelineStage
{
    private const ITEM_FIELDS = [
        'format', 'pattern', 'minLength', 'maxLength', 'minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum', 'multipleOf',
    ];

    private const STANDARD_TYPES = [
        'string', 'integer', 'boolean', 'number', 'object', '[]',
        'string[]', 'integer[]', 'boolean[]', 'number[]', 'object[]',
    ];

    /**
     * @param array<string, CustomTypeConfig> $customTypesConfig
     */
    public function __construct(
        private readonly array $customTypesConfig = []
    ) {}

    public function process(ParameterContext $context): ParameterContext
    {
        if ($this->isStandardType($context->type)) {
            return $context;
        }

        $className = $context->type;

        if ($config = $this->customTypesConfig[$className] ?? null) {
            $this->applyConfigToContext($config, $context);
            $this->setItemConstraintsAside($context);

            return $context;
        }

        $processor = CustomTypeProcessorRegistry::getInstance()->getProcessorFor($className);
        if ($processor) {
            $processor->process($className, $context);

            // A processor that sets value patterns of its own keeps them.
            if ($context->pattern !== null && $context->valuePatterns === []) {
                $context->valuePatterns = [$context->pattern];
            }

            if (!$this->isStandardType($context->type)) {
                $context->type = str_ends_with($className, '[]') ? 'string[]' : 'string';

                // The item sentence the no-processor fallback writes, so the class is still named.
                $itemSentence = 'Each item must be a ' . substr($className, 0, -2) . '.';

                if (str_ends_with($className, '[]') && ! in_array($itemSentence, $context->descriptions, true)) {
                    $context->descriptions[] = $itemSentence;
                }
            }

            $this->setItemConstraintsAside($context);

            return $context;
        }

        if (str_ends_with($className, '[]')) {
            $context->type = 'string[]';
            $context->descriptions[] = 'Each item must be a ' . substr($className, 0, -2) . '.';

            return $context;
        }

        $context->type = 'string';
        $context->descriptions[] = "Must be a {$className}.";

        return $context;
    }

    /**
     * On an array of a custom type the string and number constraints it sets
     * describe each item, so they are published under items. Attributes on
     * the property keep the array-level fields, since Laravel applies their
     * rules to the array itself.
     */
    private function setItemConstraintsAside(ParameterContext $context): void
    {
        if (! str_ends_with($context->type ?? '', '[]')) {
            return;
        }

        foreach (self::ITEM_FIELDS as $field) {
            if ($context->$field !== null) {
                $context->itemSchema[$field] = $context->$field;
                $context->$field = null;
            }
        }
    }

    private function isStandardType(?string $type): bool
    {
        if ($type === null) {
            return true;
        }

        return in_array($type, self::STANDARD_TYPES, true);
    }

    private function applyConfigToContext(CustomTypeConfig $config, ParameterContext $context): void
    {
        $context->type = $config->type;
        $context->descriptions = array_merge($context->descriptions, $config->descriptions);
        $context->pattern = $config->pattern;
        $context->valuePatterns = $config->pattern === null ? [] : [$config->pattern];
        $context->format = $config->format;
        $context->minimum = $config->minimum;
        $context->maximum = $config->maximum;
        $context->exclusiveMinimum = $config->exclusiveMinimum;
        $context->exclusiveMaximum = $config->exclusiveMaximum;
        $context->minLength = $config->minLength;
        $context->maxLength = $config->maxLength;
        $context->minItems = $config->minItems;
        $context->maxItems = $config->maxItems;
        $context->multipleOf = $config->multipleOf;
    }
}
