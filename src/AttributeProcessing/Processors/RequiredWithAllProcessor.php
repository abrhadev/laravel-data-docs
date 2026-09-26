<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\RequirementConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class RequiredWithAllProcessor extends RequirementConditionProcessor
{
    private const SENTENCE = 'Required when %s is present.';

    private const PAIR_SENTENCE = 'Required when both %s and %s are present.';

    private const ALL_SENTENCE = 'Required when all of %s are present.';

    protected function describe(object $attribute, ParameterContext $context): void
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

        $this->appendSentence($attribute, $context, match (count($codes)) {
            1       => sprintf(self::SENTENCE, $codes[0]),
            2       => sprintf(self::PAIR_SENTENCE, $codes[0], $codes[1]),
            default => sprintf(self::ALL_SENTENCE, implode(', ', $codes)),
        });
    }
}
