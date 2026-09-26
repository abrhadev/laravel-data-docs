<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\RequirementConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class RequiredUnlessProcessor extends RequirementConditionProcessor
{
    private const SENTENCE = 'Required unless %s is %s.';

    private const DEGRADED_SENTENCE = 'Required depending on the value of %s.';

    protected function describe(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || count($parameters) < 2) {
            return;
        }

        [$name, $extra] = $this->conditionParts($parameters[0]);
        $compared = [...$extra, ...(array) $parameters[1]];

        // Laravel rejects a declaration with no compared value at validation
        // time, so there is no enforced condition to state; a field written
        // 'a,b' compares a with b.
        if ($compared === []) {
            return;
        }

        $field = $this->fieldName($name);
        $values = $this->renderValues($compared);

        $this->appendSentence($attribute, $context, $values === null
            ? sprintf(self::DEGRADED_SENTENCE, $field)
            : sprintf(self::SENTENCE, $field, $values));
    }
}
