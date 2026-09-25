<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ExcludeUnlessProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input unless %s is %s.';

    private const DEGRADED_SENTENCE = 'Not validated, and removed from the validated input depending on the value of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || count($parameters) < 2) {
            return;
        }

        $field = $this->fieldName($this->extractFieldName($parameters[0]));
        $value = $this->renderValues([$parameters[1]]);

        $context->descriptions[] = $value === null
            ? sprintf(self::DEGRADED_SENTENCE, $field)
            : sprintf(self::SENTENCE, $field, $value);
    }
}
