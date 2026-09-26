<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\EndsWith;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function dateThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(DatesTimesTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function passesDateRules(string $property, mixed $value): bool
{
    $rules = DatesTimesTestData::getValidationRules([])[$property];

    return Validator::make([$property => $value], [$property => $rules])->passes();
}

it('pins that date_format accepts a value of any declared format', function () {
    expect(passesDateRules('several', '2024-01-31'))->toBeTrue()
        ->and(passesDateRules('several', '31/01/2024'))->toBeTrue()
        ->and(passesDateRules('several', '01/31/2024'))->toBeFalse()
        ->and(passesDateRules('several_array', '10:30'))->toBeTrue();
});

it('pins that date_format accepts no value for a format createFromFormat cannot parse', function () {
    expect(passesDateRules('with_c', date('c')))->toBeFalse()
        ->and(passesDateRules('with_c', '2024-01-31'))->toBeTrue()
        ->and(passesDateRules('only_c', date('c')))->toBeFalse()
        ->and(passesDateRules('only_c', '2024-01-01T10:00:00+0000'))->toBeFalse()
        ->and(passesDateRules('only_r', date('r')))->toBeFalse();
});

it('pins that Laravel splits a format at its comma', function () {
    expect(passesDateRules('with_comma', 'Thu, 17 Mar 1994'))->toBeFalse()
        ->and(passesDateRules('with_comma', 'Thu'))->toBeTrue()
        ->and(passesDateRules('with_comma', ' 17 Mar 1994'))->toBeTrue();
});

it('pins that an RFC 3339 offset needs its colon and a literal Z only matches Z', function () {
    expect(passesDateRules('atom', '2024-01-01T10:00:00+00:00'))->toBeTrue()
        ->and(passesDateRules('atom', '2024-01-01T10:00:00+0000'))->toBeFalse()
        ->and(passesDateRules('zulu', '2024-01-01T10:00:00Z'))->toBeTrue()
        ->and(passesDateRules('zulu', '2024-01-01T10:00:00+0000'))->toBeFalse();
});

it('generates an example that passes the declared date format', function (string $property) {
    foreach (range(1, 50) as $run) {
        $example = dateThroughPipeline($property)->example;

        expect(passesDateRules($property, $example))->toBeTrue("{$property}: {$example}");
    }
})->with([
    'date', 'atom', 'zulu', 'time', 'hours_minutes', 'slashes', 'us_dashes', 'us_slashes',
    'day_month', 'unlisted', 'with_comma', 'several', 'several_array', 'with_c', 'with_pattern', 'any_date', 'future_pattern', 'any_date_future', 'narrow_year', 'narrow_month', 'any_date_narrow', 'day_first_date', 'two_patterns', 'far_past_prefix', 'time_then_date', 'date_regex_insensitive', 'rule_date_then_formats',
]);

it('publishes DateFormat on an array field as a note, since date_format rejects every array', function () {
    $context = dateThroughPipeline('date_array');

    expect($context->description)->toContain('Note: must be a valid date in the format <code>Y-m-d</code>, which no array can be, so any request that sends this field fails validation.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('format')
        ->and(passesDateRules('date_array', ['2024-01-31']))->toBeFalse();
});

it('lists beside a Date, declared or written as a rule, only the formats the date rule reads', function (string $property) {
    expect(dateThroughPipeline($property)->description)->toContain('Must be a valid date in the format <code>Y-m-d</code>.')
        ->not->toContain('<code>H:i</code>');
})->with(['time_then_date', 'rule_date_then_formats']);

it('publishes Date beside a format date never reads as a note, in either order', function (string $property, string $format) {
    $context = dateThroughPipeline($property);

    expect($context->description)->toContain("Note: must be a valid date in the format <code>{$format}</code>, which the date rule never reads as a date, so any request that sends this field fails validation.")
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('format');
})->with([
    'time only'    => ['date_time_only', 'H:i'],
    'without year' => ['date_without_year', 'd/m'],
    'date as rule' => ['rule_date_time_only', 'H:i'],
]);

it('publishes Date on an array field as a note, since date rejects every array', function () {
    $context = dateThroughPipeline('any_date_array');

    expect($context->description)->toContain('Note: must be a valid date, which no array can be, so any request that sends this field fails validation.')
        ->and($context->description)->not->toContain('Must be a valid date.')
        ->and(passesDateRules('any_date_array', ['2024-01-31']))->toBeFalse();
});

it('publishes no OpenAPI format for #[Date], whose rule accepts more than a calendar date', function () {
    $context = dateThroughPipeline('any_date');

    expect($context->description)->toContain('Must be a valid date.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('format')
        ->and(passesDateRules('any_date', '2024-01-31 10:00:00'))->toBeTrue()
        ->and(passesDateRules('any_date', 'January 31, 2024'))->toBeTrue();
});

it('documents every declared format and an OpenAPI format only where it matches exactly', function (string $property, string $sentence, ?string $format) {
    $context = dateThroughPipeline($property);

    expect($context->description)->toContain($sentence)
        ->and($context->toParameter()->openApiAttributes['format'] ?? null)->toBe($format);
})->with([
    'Y-m-d'          => ['date', 'Must be a valid date in the format <code>Y-m-d</code>.', 'date'],
    'Y-m-d\TH:i:sP'  => ['atom', 'Must be a valid date in the format <code>Y-m-d\TH:i:sP</code>.', 'date-time'],
    'Y-m-d\TH:i:s\Z' => ['zulu', 'Must be a valid date in the format <code>Y-m-d\TH:i:s\Z</code>.', 'date-time'],
    'H:i:s'          => ['time', 'Must be a valid date in the format <code>H:i:s</code>.', 'time'],
    'H:i'            => ['hours_minutes', 'Must be a valid date in the format <code>H:i</code>.', null],
    'm/d/Y'          => ['us_slashes', 'Must be a valid date in the format <code>m/d/Y</code>.', null],
    'a comma'        => ['with_comma', 'Must be a valid date in one of the formats: <code>D</code>, <code> d M Y</code>.', null],
    'several'        => ['several', 'Must be a valid date in one of the formats: <code>Y-m-d</code>, <code>d/m/Y</code>.', null],
    'an array'       => ['several_array', 'Must be a valid date in one of the formats: <code>Y-m-d</code>, <code>H:i</code>.', null],
    'with c'         => ['with_c', 'Must be a valid date in the format <code>Y-m-d</code>.', 'date'],
    'only c'         => ['only_c', 'Note: must be a valid date in the format <code>c</code>, which no value can match, so any request that sends this field fails validation.', null],
]);

class DatesTimesTestData extends Data
{
    public function __construct(
        #[DateFormat('Y-m-d')]
        public string $date,
        #[DateFormat('Y-m-d\TH:i:sP')]
        public string $atom,
        #[DateFormat('Y-m-d\TH:i:s\Z')]
        public string $zulu,
        #[DateFormat('H:i:s')]
        public string $time,
        #[DateFormat('H:i')]
        public string $hours_minutes,
        #[DateFormat('Y/m/d')]
        public string $slashes,
        #[DateFormat('m-d-Y')]
        public string $us_dashes,
        #[DateFormat('m/d/Y')]
        public string $us_slashes,
        #[DateFormat('d/m')]
        public string $day_month,
        #[DateFormat('D d M Y g:i A')]
        public string $unlisted,
        #[DateFormat('D, d M Y')]
        public string $with_comma,
        #[DateFormat('Y-m-d', 'd/m/Y')]
        public string $several,
        #[DateFormat(['Y-m-d', 'H:i'])]
        public string $several_array,
        #[DateFormat('c', 'Y-m-d')]
        public string $with_c,
        #[DateFormat('c')]
        public string $only_c,
        #[DateFormat('r')]
        public string $only_r,
        #[DateFormat('Y-m-d'), StartsWith('20')]
        public string $with_pattern,
        #[Date]
        public string $any_date,
        #[DateFormat('Y-m-d'), StartsWith('205')]
        public string $future_pattern,
        /** @var string[] */
        #[DateFormat('Y-m-d')]
        public array $date_array,
        #[Date, StartsWith('205')]
        public string $any_date_future,
        #[DateFormat('Y-m-d'), StartsWith('2031')]
        public string $narrow_year,
        #[DateFormat('Y-m-d'), StartsWith('2031-05')]
        public string $narrow_month,
        #[Date, StartsWith('2031-05')]
        public string $any_date_narrow,
        #[Date, DateFormat('d/m/Y')]
        public string $day_first_date,
        #[DateFormat('Y-m-d'), StartsWith('20'), EndsWith('-01')]
        public string $two_patterns,
        #[Date, StartsWith('1850')]
        public string $far_past_prefix,
        #[Date, DateFormat('H:i', 'Y-m-d')]
        public string $time_then_date,
        #[DateFormat('Y-m-d'), Regex('/^2031/i')]
        public string $date_regex_insensitive,
        #[Rule('date'), DateFormat('H:i', 'Y-m-d')]
        public string $rule_date_then_formats,
        #[DateFormat('H:i'), Rule('date')]
        public string $rule_date_time_only,
        #[Date, DateFormat('H:i')]
        public string $date_time_only,
        #[DateFormat('d/m'), Date]
        public string $date_without_year,
        /** @var string[] */
        #[Date]
        public array $any_date_array,
    ) {}
}
