<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Date;
use DateTimeImmutable;
use DateTimeZone;
use ValueError;

final class DateFormatProcessor implements AttributeProcessor
{
    private const REFERENCE_INSTANTS = [
        '2001-02-03 04:05:06',
        '2024-11-27 16:45:30',
        '1999-12-31 23:59:59',
    ];

    private const EXACT_FORMATS = [
        'Y-m-d'          => 'date',
        'Y-m-d\TH:i:sP'  => 'date-time',
        'Y-m-d\TH:i:s\Z' => 'date-time',
        'H:i:s'          => 'time',
    ];

    public function process(object $attribute, ParameterContext $context): void
    {
        $formats = $attribute->parameters()[0] ?? [];
        $formats = is_array($formats) ? $formats : [$formats];

        if ($formats === [] || array_filter($formats, fn($format) => ! is_string($format)) !== []) {
            return;
        }

        $formats = array_values(array_unique(array_map(
            fn(?string $format) => (string) $format,
            str_getcsv(implode(',', $formats), ',', '"', '\\'),
        )));

        $documented = array_values(array_filter($formats, fn(string $format) => $this->isSatisfiable($format)));

        // date_format rejects any value that is not a string or a number.
        if (str_ends_with($context->type ?? '', '[]')) {
            $context->descriptions[] = 'Note: must be a valid date in ' . $this->formatList($documented !== [] ? $documented : $formats)
                . ', which no array can be, so any request that sends this field fails validation.';

            return;
        }

        $context->valueRules[] = 'date_format:' . implode(',', $formats);

        if ($documented === []) {
            $context->format = null;
            $context->descriptions[] = 'Note: must be a valid date in ' . $this->formatList($formats)
                . ', which no value can match, so any request that sends this field fails validation.';

            return;
        }

        // A Date beside it, declared or written as #[Rule('date')], runs date on the value too, which reads no year in H:i or d/m.
        $readable = array_filter(ReplacedRules::documentedRules($context->property), fn(object $rule) => $rule instanceof Date) !== []
            ? array_values(array_filter($documented, fn(string $format) => $this->dateReads($format)))
            : $documented;

        if ($readable === []) {
            $context->format = null;
            $context->descriptions[] = 'Note: must be a valid date in ' . $this->formatList($documented)
                . ', which the date rule never reads as a date, so any request that sends this field fails validation.';

            return;
        }

        // Only the formats a value can pass are listed: beside a Date, the ones date also reads.
        $exact = array_unique(array_map(fn(string $format) => self::EXACT_FORMATS[$format] ?? null, $readable));

        $context->format = count($exact) === 1 ? $exact[0] : null;
        $context->dateFormat = $readable[0];
        $context->descriptions[] = 'Must be a valid date in ' . $this->formatList($readable) . '.';
    }

    /**
     * Spatie joins the formats with commas into one rule string, which Laravel
     * reads back with str_getcsv, so a format containing a comma is split.
     *
     * Laravel's date_format rule accepts a value only when createFromFormat reads
     * it back unchanged, so a format with a character it cannot parse (c, r, N)
     * accepts no value at all.
     */
    private function isSatisfiable(string $format): bool
    {
        $utc = new DateTimeZone('UTC');

        foreach (self::REFERENCE_INSTANTS as $instant) {
            $value = (new DateTimeImmutable($instant, $utc))->format($format);

            try {
                $parsed = DateTimeImmutable::createFromFormat('!' . $format, $value, $utc);
            } catch (ValueError) {
                return false;
            }

            if ($parsed !== false && $parsed->format($format) == $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether Laravel's date rule reads a value in the format as a calendar
     * date for some reference instant: strtotime reads it and date_parse finds
     * a year, month and day.
     */
    private function dateReads(string $format): bool
    {
        foreach (self::REFERENCE_INSTANTS as $instant) {
            $value = (new DateTimeImmutable($instant, new DateTimeZone('UTC')))->format($format);
            $date = date_parse($value);

            if (strtotime($value) !== false && is_int($date['year']) && is_int($date['month']) && is_int($date['day']) && checkdate($date['month'], $date['day'], $date['year'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $formats
     */
    private function formatList(array $formats): string
    {
        $codes = implode(', ', array_map(fn(string $format) => "<code>{$format}</code>", $formats));

        return count($formats) === 1 ? "the format {$codes}" : "one of the formats: {$codes}";
    }
}
