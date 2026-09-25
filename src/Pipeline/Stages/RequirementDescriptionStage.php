<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;

final class RequirementDescriptionStage implements ParameterPipelineStage
{
    private const PRESENT_SENTENCE = 'Must be included in the request, but may be empty.';

    private const SENTENCE = 'Only validated when included in the request.';

    private const NULLABLE_SENTENCE = 'A null value is accepted.';

    public function process(ParameterContext $context): ParameterContext
    {
        if ($context->presentAcceptsEmpty) {
            $context->description = trim($context->description . ' ' . self::PRESENT_SENTENCE);
        }

        if ($context->onlyValidatedWhenPresent) {
            $context->description = trim($context->description . ' ' . self::SENTENCE);
        }

        if ($context->nullable === true) {
            $context->description = trim($context->description . ' ' . self::NULLABLE_SENTENCE);
        }

        return $context;
    }
}
