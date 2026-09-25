<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ProhibitsProcessor extends ConditionProcessor
{
    private const SINGLE_SENTENCE = 'Sending this field forbids sending %s; the request is rejected if both are sent.';

    private const MULTI_SENTENCE = 'Sending this field forbids sending %s; the request is rejected if any of them is also sent.';

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

        $fields = array_map(fn($name) => $this->fieldName($name), $names);

        $context->descriptions[] = count($fields) === 1
            ? sprintf(self::SINGLE_SENTENCE, $fields[0])
            : sprintf(self::MULTI_SENTENCE, $this->joinFields($fields));
    }

    /**
     * @param array<int, string> $fields
     */
    private function joinFields(array $fields): string
    {
        $last = array_pop($fields);

        return implode(', ', $fields) . ' and ' . $last;
    }
}
