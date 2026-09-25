<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Prohibited;

final class ProhibitedProcessor extends ConditionProcessor
{
    private const SENTENCE = 'Must not be sent; the request is rejected if it is.';

    private const CONDITIONAL_SENTENCE = 'Must not be sent in some requests; the request is rejected if it is.';

    public function process(object $attribute, ParameterContext $context): void
    {
        // Loose equality is true only when no rule object is wrapped, whose condition cannot be read.
        $context->descriptions[] = $attribute == new Prohibited()
            ? self::SENTENCE
            : self::CONDITIONAL_SENTENCE;
    }
}
