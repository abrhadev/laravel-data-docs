<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use BackedEnum;
use UnitEnum;

final class DefaultValueDescriptionStage implements ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext
    {
        if ($context->default !== null) {
            $defaultDescription = $this->getDefaultValueDescription($context);
            $context->description = $this->appendDescription($context->description, $defaultDescription);
        }

        return $context;
    }

    private function getDefaultValueDescription(ParameterContext $context): string
    {
        $defaultValue = $context->property->defaultValue ?? null;

        if ($defaultValue instanceof BackedEnum) {
            return "Defaults to <code>{$defaultValue->name}</code> ({$defaultValue->value}).";
        }

        $value = match (true) {
            $defaultValue instanceof UnitEnum                         => $defaultValue->name,
            is_bool($context->default)                                => $context->default ? 'true' : 'false',
            is_array($context->default), is_object($context->default) => json_encode($context->default),
            default                                                   => $context->default,
        };

        return "Defaults to <code>{$value}</code>.";
    }

    private function appendDescription(string $existing, string $new): string
    {
        return trim($existing . ' ' . $new);
    }
}
