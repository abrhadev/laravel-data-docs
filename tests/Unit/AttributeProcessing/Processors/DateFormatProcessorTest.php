<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateFormatProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

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
