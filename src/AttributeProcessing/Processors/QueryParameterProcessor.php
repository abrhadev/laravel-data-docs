<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;

final class QueryParameterProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $context->location = ParameterLocation::QUERY;
    }
}
