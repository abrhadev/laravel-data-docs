<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class SameProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Must match the value of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || !isset($parameters[0])) {
            return;
        }

        $name = $this->extractFieldName($parameters[0]);

        if ($name === '') {
            return;
        }

        $context->descriptions[] = sprintf(self::SENTENCE, $this->fieldName($name));
    }
}
