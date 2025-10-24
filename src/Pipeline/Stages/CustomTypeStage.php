<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessorRegistry;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\ValueObjects\CustomTypeConfig;

final class CustomTypeStage implements ParameterPipelineStage
{
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

            return $context;
        }

        $processor = CustomTypeProcessorRegistry::getInstance()->getProcessorFor($className);
        if ($processor) {
            $processor->process($className, $context);

            return $context;
        }

        $context->type = 'string';
        $context->descriptions[] = "Must be a {$className}";

        return $context;
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
