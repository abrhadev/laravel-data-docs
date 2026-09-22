<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;

final class RequirementDescriptionStage implements ParameterPipelineStage
{
    private const SENTENCE = 'Only validated when included in the request.';

    public function process(ParameterContext $context): ParameterContext
    {
        if ($context->onlyValidatedWhenPresent) {
            $context->description = trim($context->description . ' ' . self::SENTENCE);
        }

        return $context;
    }
}
