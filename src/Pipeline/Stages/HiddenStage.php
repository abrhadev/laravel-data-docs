<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Attributes\Hidden;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;

final class HiddenStage implements ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext
    {
        $context->isHidden = $context->property->attributes->has(Hidden::class);

        return $context;
    }
}
