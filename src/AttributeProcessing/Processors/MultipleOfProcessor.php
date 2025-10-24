<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class MultipleOfProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if ($value !== null) {
            $context->multipleOf = $value;
            $context->descriptions[] = "Must be a multiple of {$value}.";
        }
    }
}
