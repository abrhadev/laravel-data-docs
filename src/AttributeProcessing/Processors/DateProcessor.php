<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DateProcessor implements AttributeProcessor
{
    private const SENTENCE = 'Must be a valid date.';

    private const ARRAY_NOTE = 'Note: must be a valid date, which no array can be, so any request that sends this field fails validation.';

    public function process(object $attribute, ParameterContext $context): void
    {
        // date rejects any value that is not a string, a number or a date object.
        if (str_ends_with($context->type ?? '', '[]')) {
            $context->descriptions[] = self::ARRAY_NOTE;

            return;
        }

        $context->descriptions[] = self::SENTENCE;
        $context->exampleFormat = 'date';
        $context->valueRules[] = 'date';
    }
}
