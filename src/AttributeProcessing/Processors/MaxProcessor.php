<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\SizeBasedProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class MaxProcessor extends SizeBasedProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if ($value !== null) {
            $unit = $this->getUnit($context, $value);
            $this->applyConstraint($context, 'max', $value);

            $context->descriptions[] = $unit
                ? "Must have maximum <code>{$value}</code> {$unit}."
                : "Must be maximum <code>{$value}</code>.";
        }
    }
}
