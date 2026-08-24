<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DescriptionProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $context->descriptions = [...$context->descriptions, ...$attribute->descriptions];
    }
}
