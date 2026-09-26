<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ComparisonProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class GreaterThanProcessor extends ComparisonProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $value = $parameters[0] ?? null;

        if ($value !== null) {
            $this->compare($context, $value, 'gt');
        }
    }
}
