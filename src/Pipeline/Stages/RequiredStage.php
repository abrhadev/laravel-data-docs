<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;

final class RequiredStage implements ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext
    {
        $context->nullable = $context->property->type->isNullable;
        $context->required = !$context->property->type->isNullable && !$context->property->type->isOptional && !$context->property->hasDefaultValue;

        return $context;
    }
}
