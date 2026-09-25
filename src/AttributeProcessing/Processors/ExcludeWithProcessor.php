<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ExcludeWithProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input when %s is present.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || !isset($parameters[0])) {
            return;
        }

        $context->descriptions[] = sprintf(
            self::SENTENCE,
            $this->fieldName($this->extractFieldName($parameters[0]))
        );
    }
}
