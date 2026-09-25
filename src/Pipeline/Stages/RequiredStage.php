<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\Pipeline\Support\RequirementResolver;

/**
 * Sole authority on required, nullable, onlyValidatedWhenPresent and
 * presentAcceptsEmpty. Assigns
 * unconditionally, so anything an earlier stage wrote to those fields is
 * replaced; no attribute processor should be registered for Required, Nullable
 * or Sometimes.
 */
final class RequiredStage implements ParameterPipelineStage
{
    public function __construct(
        private readonly RequirementResolver $resolver,
    ) {}

    public function process(ParameterContext $context): ParameterContext
    {
        $status = $this->resolver->resolve($context->property);

        if ($status === null) {
            return $this->applyTypeDerivedFallback($context);
        }

        $context->required = $status->required;
        $context->nullable = $status->nullable;
        $context->onlyValidatedWhenPresent = $status->onlyValidatedWhenPresent;
        $context->presentAcceptsEmpty = $status->presentAcceptsEmpty;

        return $context;
    }

    private function applyTypeDerivedFallback(ParameterContext $context): ParameterContext
    {
        $type = $context->property->type;

        $context->nullable = $type->isNullable;
        $context->required = !$type->isNullable
            && !$type->isOptional
            && !$context->property->hasDefaultValue;
        $context->onlyValidatedWhenPresent = $type->isOptional;
        $context->presentAcceptsEmpty = false;

        return $context;
    }
}
