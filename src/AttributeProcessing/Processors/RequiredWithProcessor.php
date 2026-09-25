<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\RequirementConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class RequiredWithProcessor extends RequirementConditionProcessor
{
    private const SENTENCE = 'Required when %s is present.';

    private const ANY_SENTENCE = 'Required when any of %s is present.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null) {
            return;
        }

        $names = $this->fieldNames((array) ($parameters[0] ?? []));

        if ($names === []) {
            return;
        }

        $codes = array_map(fn($name) => $this->fieldName($name), $names);

        $this->appendSentence($attribute, $context, count($codes) === 1
            ? sprintf(self::SENTENCE, $codes[0])
            : sprintf(self::ANY_SENTENCE, implode(', ', $codes)));
    }
}
