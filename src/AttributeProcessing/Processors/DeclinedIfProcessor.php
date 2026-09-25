<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\AcceptanceProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DeclinedIfProcessor extends AcceptanceProcessor
{
    private const SENTENCE = 'Must be declined when %s is %s, by sending one of %s.';

    private const DEGRADED_SENTENCE = 'Must be declined when %s has certain values, by sending one of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || count($parameters) < 2) {
            return;
        }

        $field = $this->fieldName($this->extractFieldName($parameters[0]));
        $value = $this->renderValues([$parameters[1]]);
        $list = $this->valueList(self::DECLINED_VALUES);

        $context->descriptions[] = $value === null
            ? sprintf(self::DEGRADED_SENTENCE, $field, $list)
            : sprintf(self::SENTENCE, $field, $value, $list);
    }
}
