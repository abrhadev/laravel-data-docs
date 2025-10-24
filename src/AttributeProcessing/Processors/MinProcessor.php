<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\SizeBasedProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class MinProcessor extends SizeBasedProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if ($value !== null) {
            $unit = $this->getUnit($context, $value);
            $this->applyConstraint($context, 'min', $value);

            $context->descriptions[] = $unit
                ? "Must have minimum <code>{$value}</code> {$unit}."
                : "Must be minimum <code>{$value}</code>.";
        }
    }
}
