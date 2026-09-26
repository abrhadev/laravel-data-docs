<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ProhibitionExclusionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ExcludeWithoutProcessor extends ProhibitionExclusionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input when %s is not present.';

    private const ANY_SENTENCE = 'Not validated, and removed from the validated input when any of %s is not present.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || !isset($parameters[0])) {
            return;
        }

        // Unlike exclude_with, Laravel checks every field 'a,b' names.
        $fields = array_map(fn(string $name) => $this->fieldName($name), $this->fieldNames([$parameters[0]]));

        $this->appendEnforced($attribute, $context, count($fields) === 1
            ? sprintf(self::SENTENCE, $fields[0])
            : sprintf(self::ANY_SENTENCE, implode(', ', $fields)));
    }
}
