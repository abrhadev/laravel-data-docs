<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\ValueListProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Illuminate\Validation\Rules\NotIn as BaseNotIn;
use Spatie\LaravelData\Attributes\Validation\NotIn;

final class NotInProcessor extends ValueListProcessor
{
    /**
     * Records the rejected set and, on a field that is not an array, removes it
     * from the accepted set. On an array Laravel's not_in only rejects an array
     * whose items are all forbidden, so an accepted array may still hold one.
     */
    public function process(object $attribute, ParameterContext $context): void
    {
        $values = $this->declaredValues($attribute, NotIn::class, BaseNotIn::class);

        if ($values === null) {
            return;
        }

        // A boolean field fails its own type rule for any other value, so only its forms are worth stating.
        $recorded = str_replace('[]', '', $context->type ?? '') === 'boolean' ? $this->keepMatchingType($context, $values) : $values;

        $context->excludedValues = array_values(array_unique([...$context->excludedValues ?? [], ...$recorded]));

        if ($this->isArrayField($context)) {
            return;
        }

        if ($context->enumInfo !== null) {
            $this->narrowEnum($context, fn(?string $case) => !in_array($case, $values, true));

            return;
        }

        if ($context->allowedValues !== null) {
            $context->allowedValues = array_values(array_diff($context->allowedValues, $values));
        }
    }
}
