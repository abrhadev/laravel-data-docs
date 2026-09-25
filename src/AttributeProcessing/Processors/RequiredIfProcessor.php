<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\RequirementConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class RequiredIfProcessor extends RequirementConditionProcessor
{
    private const SENTENCE = 'Required when %s is %s.';

    private const DEGRADED_SENTENCE = 'Required depending on the value of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || count($parameters) < 2) {
            return;
        }

        $compared = (array) $parameters[1];

        // Laravel rejects a declaration with no compared value at validation
        // time, so there is no enforced condition to state.
        if ($compared === []) {
            return;
        }

        $field = $this->fieldName($this->extractFieldName($parameters[0]));
        $values = $this->renderValues($compared);

        $this->appendSentence($attribute, $context, $values === null
            ? sprintf(self::DEGRADED_SENTENCE, $field)
            : sprintf(self::SENTENCE, $field, $values));
    }
}
