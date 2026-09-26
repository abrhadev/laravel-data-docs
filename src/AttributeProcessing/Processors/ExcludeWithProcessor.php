<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ProhibitionExclusionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ExcludeWithProcessor extends ProhibitionExclusionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input when %s is present.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null || !isset($parameters[0])) {
            return;
        }

        $this->appendEnforced($attribute, $context, sprintf(
            self::SENTENCE,
            // Laravel reads the first field only, so 'a,b' is excluded when a is present.
            $this->fieldName($this->fieldNames([$parameters[0]])[0])
        ));
    }
}
