<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ValueListProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Illuminate\Validation\Rules\In as BaseIn;
use Spatie\LaravelData\Attributes\Validation\In;

final class InProcessor extends ValueListProcessor
{
    /**
     * Records the accepted set; TypeDescriptionStage writes the sentence once a
     * NotIn declared after this one has also been applied.
     */
    public function process(object $attribute, ParameterContext $context): void
    {
        $values = $this->declaredValues($attribute, In::class, BaseIn::class);

        if ($values === null) {
            return;
        }

        // A PHP-enum property only ever holds one of its cases (Laravel Data
        // adds an Enum rule for a backed enum), so only the cases the In also
        // accepts remain.
        if ($context->enumInfo !== null) {
            $this->narrowEnum($context, fn(?string $case) => in_array($case, $values, true));

            return;
        }

        $values = $this->keepMatchingType($context, $values);

        if ($context->allowedValues !== null) {
            $values = array_values(array_intersect($context->allowedValues, $values));
        }

        if (!$this->isArrayField($context) && $context->excludedValues !== null) {
            $values = array_values(array_diff($values, $context->excludedValues));
        }

        $context->allowedValues = $values;
    }
}
