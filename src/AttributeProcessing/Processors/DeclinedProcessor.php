<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\AcceptanceProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DeclinedProcessor extends AcceptanceProcessor
{
    private const SENTENCE = 'Must be sent as one of %s.';

    public function process(object $attribute, ParameterContext $context): void
    {
        $context->descriptions[] = sprintf(self::SENTENCE, $this->valueList(self::DECLINED_VALUES));
        $this->applyExample($context, false);
    }
}
