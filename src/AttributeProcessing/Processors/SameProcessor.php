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

        // Laravel reads the first field of a reference written 'a,b'.
        $name = $this->fieldNames([$parameters[0]])[0];

        if ($name === '') {
            return;
        }

        $context->descriptions[] = sprintf(self::SENTENCE, $this->fieldName($name));
    }
}
