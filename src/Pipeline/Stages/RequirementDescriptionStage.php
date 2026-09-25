<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;

final class RequirementDescriptionStage implements ParameterPipelineStage
{
    private const PRESENT_SENTENCE = 'Must be included in the request, but may be empty.';

    private const SENTENCE = 'Only validated when included in the request.';

    public const NULLABLE_SENTENCE = 'A null value is accepted.';

    private const NEVER_SATISFIABLE_SENTENCE = 'Note: this field is both required and prohibited, so no request can pass validation.';

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

        if ($context->neverSatisfiable) {
            $context->description = trim($context->description . ' ' . self::NEVER_SATISFIABLE_SENTENCE);
        }

        return $context;
    }
}
