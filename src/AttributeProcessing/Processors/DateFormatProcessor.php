<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class DateFormatProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $format = $parameters[0] ?? null;

        if ($format !== null) {
            $formatString = is_array($format) ? $format[0] : $format;
            $context->format = $this->mapDateFormat($formatString);
            $context->descriptions[] = "Must be a valid date in the format <code>{$formatString}</code>.";
        }
    }

    private function mapDateFormat(string $format): string
    {
        if (in_array($format, ['Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:sP', 'c'])) {
            return 'date-time';
        } elseif (in_array($format, ['H:i:s', 'H:i'])) {
            return 'time';
        } elseif (in_array($format, ['Y-m-d', 'Y/m/d', 'm-d-Y', 'm/d/Y'])) {
            return 'date';
        }

        return 'date';
    }
}
