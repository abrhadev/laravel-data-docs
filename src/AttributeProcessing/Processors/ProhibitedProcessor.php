<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ProhibitionExclusionProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Prohibited;

final class ProhibitedProcessor extends ProhibitionExclusionProcessor
{
    private const SENTENCE = 'Must not be sent; the request is rejected if it is.';

    private const CONDITIONAL_SENTENCE = 'Must not be sent in some requests; the request is rejected if it is.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $this->appendEnforced($attribute, $context, $attribute instanceof Prohibited && ReplacedRules::isBare($attribute)
            ? self::SENTENCE
            : self::CONDITIONAL_SENTENCE);
    }
}
