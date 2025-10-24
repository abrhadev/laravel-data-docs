<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class RegexProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $pattern = $parameters[0] ?? null;

        if ($pattern !== null) {
            $context->pattern = $pattern;
            $context->descriptions[] = "Must match the regex <code>{$pattern}</code>.";
        }
    }
}
