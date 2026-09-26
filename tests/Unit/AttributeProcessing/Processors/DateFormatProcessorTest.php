<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateFormatProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new DateFormatProcessor();
});

it('maps Y-m-d format to date', function () {
    $testData = new class ('2024-01-01') extends Data {
        public function __construct(
            #[DateFormat('Y-m-d')]
            public string $birthDate,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'birthDate');

    $context = new ParameterContext('birthDate', $property);
    $context->type = 'string';

    $attribute = new DateFormat('Y-m-d');
    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('date')
        ->and($context->descriptions)->toContain('Must be a valid date in the format <code>Y-m-d</code>.');
});

it('maps date-time formats to date-time', function () {
    $testData = new class ('2024-01-01T12:00:00Z') extends Data {
        public function __construct(
            #[DateFormat('Y-m-d\TH:i:s\Z')]
            public string $timestamp,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'timestamp');

    $context = new ParameterContext('timestamp', $property);
    $context->type = 'string';

    $attribute = new DateFormat('Y-m-d\TH:i:s\Z');
    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('date-time');
});

it('maps time formats to time', function () {
    $testData = new class ('12:00:00') extends Data {
        public function __construct(
            #[DateFormat('H:i:s')]
            public string $time,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'time');

    $context = new ParameterContext('time', $property);
    $context->type = 'string';

    $attribute = new DateFormat('H:i:s');
    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('time');
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new DateFormat(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->format)->toBeNull();
});

it('publishes no OpenAPI format that values of the declared format would violate', function (string $format) {
    $context = conditionContext();
    $context->type = 'string';
    $context->format = 'date';

    $this->processor->process(new DateFormat($format), $context);

    expect($context->format)->toBeNull()
        ->and($context->dateFormat)->toBe($format)
        ->and($context->descriptions)->toBe(["Must be a valid date in the format <code>{$format}</code>."]);
})->with(['H:i', 'Y/m/d', 'm-d-Y', 'm/d/Y', 'Ymd']);

it('publishes the OpenAPI format a declared format matches exactly', function (string $format, string $openApi) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new DateFormat($format), $context);

    expect($context->format)->toBe($openApi)
        ->and($context->dateFormat)->toBe($format);
})->with([
    ['Y-m-d', 'date'],
    ['Y-m-d\TH:i:sP', 'date-time'],
    ['Y-m-d\TH:i:s\Z', 'date-time'],
    ['H:i:s', 'time'],
]);

it('documents every declared format', function (DateFormat $attribute) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->descriptions)->toBe(['Must be a valid date in one of the formats: <code>Y-m-d</code>, <code>d/m/Y</code>.'])
        ->and($context->format)->toBeNull()
        ->and($context->dateFormat)->toBe('Y-m-d');
})->with([
    'variadic' => fn() => new DateFormat('Y-m-d', 'd/m/Y'),
    'an array' => fn() => new DateFormat(['Y-m-d', 'd/m/Y']),
    'repeated' => fn() => new DateFormat('Y-m-d', 'd/m/Y', 'Y-m-d'),
]);

it('publishes an OpenAPI format for several formats only when all of them map to it', function (array $formats, ?string $openApi) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new DateFormat(...$formats), $context);

    expect($context->format)->toBe($openApi);
})->with([
    'both date-time' => [['Y-m-d\TH:i:sP', 'Y-m-d\TH:i:s\Z'], 'date-time'],
    'date and time'  => [['Y-m-d', 'H:i:s'], null],
    'date and other' => [['Y-m-d', 'd/m/Y'], null],
]);

it('leaves out a declared format that no value can match', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new DateFormat('c', 'Y-m-d'), $context);

    expect($context->descriptions)->toBe(['Must be a valid date in the format <code>Y-m-d</code>.'])
        ->and($context->format)->toBe('date')
        ->and($context->dateFormat)->toBe('Y-m-d');
});

it('publishes a note when no declared format can match any value', function (array $formats, string $sentence) {
    $context = conditionContext();
    $context->type = 'string';
    $context->format = 'date-time';

    $this->processor->process(new DateFormat(...$formats), $context);

    expect($context->descriptions)->toBe([$sentence])
        ->and($context->format)->toBeNull()
        ->and($context->dateFormat)->toBeNull();
})->with([
    'c'       => [['c'], 'Note: must be a valid date in the format <code>c</code>, which no value can match, so any request that sends this field fails validation.'],
    'c and r' => [['c', 'r'], 'Note: must be a valid date in one of the formats: <code>c</code>, <code>r</code>, which no value can match, so any request that sends this field fails validation.'],
]);

it('documents nothing when an external reference hides one of the formats', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new DateFormat('Y-m-d', new RouteParameterReference('format')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->format)->toBeNull()
        ->and($context->dateFormat)->toBeNull();
});

it('documents nothing for a declaration without formats', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new DateFormat(), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->dateFormat)->toBeNull();
});

it('lists only the satisfiable formats in the array-type note, like every other sentence variant', function () {
    $context = conditionContext();
    $context->type = 'string[]';

    $this->processor->process(new DateFormat('c', 'Y-m-d'), $context);

    expect($context->descriptions)->toBe([
        'Note: must be a valid date in the format <code>Y-m-d</code>, which no array can be, so any request that sends this field fails validation.',
    ])
        ->and($context->format)->toBeNull()
        ->and($context->dateFormat)->toBeNull();
});

it('falls back to every declared format in the array-type note when none is satisfiable', function () {
    $context = conditionContext();
    $context->type = 'string[]';

    $this->processor->process(new DateFormat('c', 'r'), $context);

    expect($context->descriptions)->toBe([
        'Note: must be a valid date in one of the formats: <code>c</code>, <code>r</code>, which no array can be, so any request that sends this field fails validation.',
    ]);
});
