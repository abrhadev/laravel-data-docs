<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ProhibitionExclusionProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Exclude;

final class ExcludeProcessor extends ProhibitionExclusionProcessor
{
    private const SENTENCE = 'Not validated, and removed from the validated input.';

    private const CONDITIONAL_SENTENCE = 'Not validated, and removed from the validated input, in some requests.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $this->appendEnforced($attribute, $context, $attribute instanceof Exclude && ReplacedRules::isBare($attribute)
            ? self::SENTENCE
            : self::CONDITIONAL_SENTENCE);
    }
}
