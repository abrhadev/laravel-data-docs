<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ConditionProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\ConfirmationCompanion;

/**
 * Laravel Data's Confirmed accepts no argument (PHP 8.4 discards one; PHP 8.3
 * rejects the declaration), so the default name is what runtime enforces. A
 * name is read from parameters() only so that an upstream change is followed
 * rather than contradicted.
 */
final class ConfirmedProcessor extends ConditionProcessor
{
    private const SOURCE_SENTENCE = 'A matching %s value must be sent with it.';

    private const MATCH_SENTENCE = 'Must match the value of %s.';

    private const REQUIRED_WHEN_SENT_SENTENCE = 'Required when %s is sent.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $this->parametersOf($attribute);

        if ($parameters === null) {
            return;
        }

        $custom = isset($parameters[0]) ? $this->extractFieldName($parameters[0]) : '';
        // Laravel reads the confirmation under the input name the request uses.
        $input = $context->property->inputMappedName ?? $context->property->name;
        $name = $custom !== '' ? $custom : $input . '_confirmation';
        $source = $this->fieldName($input);

        $context->descriptions[] = sprintf(self::SOURCE_SENTENCE, $this->fieldName($name));
        $context->confirmationCompanion = new ConfirmationCompanion(
            $name,
            sprintf(self::MATCH_SENTENCE, $source),
            sprintf(self::REQUIRED_WHEN_SENT_SENTENCE, $source),
        );
    }
}
