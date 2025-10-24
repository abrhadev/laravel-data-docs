<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\SizeBasedProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class BetweenProcessor extends SizeBasedProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $min = $parameters[0] ?? null;
        $max = $parameters[1] ?? null;

        if ($min !== null && $max !== null) {
            $unit = $this->getUnit($context, $min === 1 && $max === 1 ? 1 : 2);
            $this->applyConstraint($context, 'min', $min);
            $this->applyConstraint($context, 'max', $max);

            $context->descriptions[] = $unit
                ? "Must have between <code>{$min}</code> and <code>{$max}</code> {$unit}."
                : "Must be between <code>{$min}</code> and <code>{$max}</code>.";
        }
    }
}
