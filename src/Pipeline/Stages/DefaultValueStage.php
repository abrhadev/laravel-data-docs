<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use BackedEnum;
use UnitEnum;

final class DefaultValueStage implements ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext
    {
        if ($context->property->hasDefaultValue) {
            $defaultValue = $context->property->defaultValue;
            if ($defaultValue instanceof BackedEnum) {
                $defaultValue = $defaultValue->value;
            } elseif ($defaultValue instanceof UnitEnum) {
                $defaultValue = $defaultValue->name;
            }
            $context->default = $defaultValue;
        }

        return $context;
    }
}
