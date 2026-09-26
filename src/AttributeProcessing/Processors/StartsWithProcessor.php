<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class StartsWithProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();

        $flatParameters = is_array($parameters[0] ?? null) ? $parameters[0] : $parameters;

        if (array_filter($flatParameters, 'is_string') !== $flatParameters) {
            return;
        }

        // Spatie joins the values with commas and Laravel splits them back as
        // CSV, skipping an empty one, so 'a,b' means a or b.
        $needles = array_values(array_unique(array_filter(
            str_getcsv(implode(',', $flatParameters), ',', '"', '\\'),
            fn(?string $needle) => $needle !== null && $needle !== ''
        )));

        if ($needles === []) {
            $context->descriptions[] = 'Note: must start with a value, but none is given, so any request that sends this field fails validation.';

            return;
        }

        $escapedValues = array_map(fn($arg) => preg_quote($arg, '/'), $needles);
        $context->pattern = '^(' . implode('|', $escapedValues) . ')';
        $context->valuePatterns[] = $context->pattern;
        $formattedValues = array_map(fn($arg) => "<code>$arg</code>", $needles);
        $context->descriptions[] = 'Must start with one of: ' . implode(', ', $formattedValues) . '.';
    }
}
