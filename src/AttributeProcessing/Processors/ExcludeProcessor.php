<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Exclude;

final class ExcludeProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input.';

    private const CONDITIONAL_SENTENCE = 'Not validated, and removed from the validated input, in some requests.';

    public function process(object $attribute, ParameterContext $context): void
    {
        // Loose equality is true only when no rule object is wrapped, whose condition cannot be read.
        $context->descriptions[] = $attribute == new Exclude()
            ? self::SENTENCE
            : self::CONDITIONAL_SENTENCE;
    }
}
