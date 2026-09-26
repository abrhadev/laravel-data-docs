<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\SizeBasedProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class SizeProcessor extends SizeBasedProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if ($this->isLiteral($value)) {
            $unit = $this->getUnit($context, $value);

            if ($this->rangeIsEmpty($context, $value, $value)) {
                $context->descriptions[] = $unit
                    ? "Note: must have exactly <code>{$value}</code> {$unit}, which no {$this->impossibleSubject($context)} can have, {$this->impossibleOutcome($context, $value, $value)}."
                    : "Note: must be exactly <code>{$value}</code>, which no {$this->impossibleSubject($context)} can be, {$this->impossibleOutcome($context, $value, $value)}.";

                return;
            }

            $this->applyConstraint($context, 'min', $value);
            $this->applyConstraint($context, 'max', $value);

            $context->descriptions[] = $unit
                ? "Must have exactly <code>{$value}</code> {$unit}."
                : "Must be exactly <code>{$value}</code>.";
        }
    }
}
