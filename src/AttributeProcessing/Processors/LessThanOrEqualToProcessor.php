<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ComparisonProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class LessThanOrEqualToProcessor extends ComparisonProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if ($value !== null) {
            $valueStr = $this->extractValue($value);
            $context->maximum = is_numeric($valueStr) ? (int) $valueStr : null;
            $context->descriptions[] = "Must be less than or equal to <code>{$valueStr}</code>.";
        }
    }
}
