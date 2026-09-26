<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DifferentProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Must differ from the value of %s.';

    private const EACH_SENTENCE = 'Must differ from the value of each of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || !isset($parameters[0])) {
            return;
        }

        // Laravel checks every field of a reference written 'a,b'.
        $names = array_values(array_filter($this->fieldNames([$parameters[0]]), fn(string $name) => $name !== ''));

        if ($names === []) {
            return;
        }

        $fields = array_map(fn(string $name) => $this->fieldName($name), $names);

        $context->descriptions[] = count($fields) === 1
            ? sprintf(self::SENTENCE, $fields[0])
            : sprintf(self::EACH_SENTENCE, implode(', ', $fields));
    }
}
