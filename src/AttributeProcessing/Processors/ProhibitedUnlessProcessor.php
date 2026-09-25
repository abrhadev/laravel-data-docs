<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ProhibitedUnlessProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Must not be sent unless %s is %s; the request is rejected if it is.';

    private const DEGRADED_SENTENCE = 'Must not be sent unless %s has certain values; the request is rejected if it is.';

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

        $context->descriptions[] = $values === null
            ? sprintf(self::DEGRADED_SENTENCE, $field)
            : sprintf(self::SENTENCE, $field, $values);
    }
}
