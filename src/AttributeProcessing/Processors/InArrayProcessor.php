<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class InArrayProcessor extends ConditionProcessor
{
    private const MEMBER_SENTENCE = 'Must be one of the values submitted in %s.';

    private const EQUAL_SENTENCE = 'Must equal the value of %s.';

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

        // in_array matches keys by pattern, so only a wildcard reference tests membership of an array field.
        $context->descriptions[] = str_contains($name, '*')
            ? sprintf(self::MEMBER_SENTENCE, $this->fieldName(str_ends_with($name, '.*') ? substr($name, 0, -2) : $name))
            : sprintf(self::EQUAL_SENTENCE, $this->fieldName($name));
    }
}
