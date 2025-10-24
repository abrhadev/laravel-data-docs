<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessorRegistry;
use Abrha\LaravelDataDocs\Attributes\DataDocsAttribute;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Spatie\LaravelData\Support\Validation\ValidationRule;

final class AttributeProcessingStage implements ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext
    {
        $registry = AttributeProcessorRegistry::getInstance();

        foreach ($context->property->attributes->all(DataDocsAttribute::class) as $attribute) {
            $processor = $registry->getProcessorFor($attribute::class);
            $processor?->process($attribute, $context);
        }
        foreach ($context->property->attributes->all(ValidationRule::class) as $rule) {
            $processor = $registry->getProcessorFor($rule::class);
            $processor?->process($rule, $context);
        }

        if (!empty($context->descriptions)) {
            $context->description = $this->combineDescriptions(
                $context->description,
                $context->descriptions
            );
        }

        return $context;
    }

    private function combineDescriptions(string $baseDescription, array $descriptions): string
    {
        $allDescriptions = array_filter([$baseDescription, ...$descriptions]);

        return implode(' ', $allDescriptions);
    }
}
