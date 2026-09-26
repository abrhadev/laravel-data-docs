<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ProhibitionExclusionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ExcludeIfProcessor extends ProhibitionExclusionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input when %s is %s.';

    private const DEGRADED_SENTENCE = 'Not validated, and removed from the validated input depending on the value of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || count($parameters) < 2) {
            return;
        }

        [$name, $extra] = $this->conditionParts($parameters[0]);
        $field = $this->fieldName($name);
        $value = $this->renderValues([...$extra, $parameters[1]]);

        $this->appendEnforced($attribute, $context, $value === null
            ? sprintf(self::DEGRADED_SENTENCE, $field)
            : sprintf(self::SENTENCE, $field, $value));
    }
}
