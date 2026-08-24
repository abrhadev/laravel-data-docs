<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DescriptionProcessor;
use Abrha\LaravelDataDocs\Attributes\Description;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new DescriptionProcessor();
});

it('appends a single description from the attribute', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Description('The unique identifier of the user')]
            public string $field,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'field');

    $context = new ParameterContext('field', $property);

    $attribute = new Description('The unique identifier of the user');
    $this->processor->process($attribute, $context);

    expect($context->descriptions)->toBe(['The unique identifier of the user']);
});

it('appends multiple descriptions from the attribute', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Description('First sentence.', 'Second sentence.')]
            public string $field,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'field');

    $context = new ParameterContext('field', $property);

    $attribute = new Description('First sentence.', 'Second sentence.');
    $this->processor->process($attribute, $context);

    expect($context->descriptions)->toBe(['First sentence.', 'Second sentence.']);
});

it('does not modify descriptions when the attribute has no values', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            public string $field,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'field');

    $context = new ParameterContext('field', $property);
    $context->descriptions = ['Existing'];

    $attribute = new Description();
    $this->processor->process($attribute, $context);

    expect($context->descriptions)->toBe(['Existing']);
});
