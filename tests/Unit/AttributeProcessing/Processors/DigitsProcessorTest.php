<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new DigitsProcessor();
});

it('sets minimum and maximum for digit count', function () {
    $testData = new class ('1234') extends Data {
        public function __construct(
            #[Digits(4)]
            public string $pinCode,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'pinCode');

    $context = new ParameterContext('pinCode', $property);
    $context->type = 'string';

    $attribute = new Digits(4);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(1000)
        ->and($context->maximum)->toBe(9999)
        ->and($context->descriptions)->toContain('Must have exactly <code>4</code> digits.');
});

it('handles single digit', function () {
    $testData = new class ('5') extends Data {
        public function __construct(
            #[Digits(1)]
            public string $digit,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'digit');

    $context = new ParameterContext('digit', $property);
    $context->type = 'string';

    $attribute = new Digits(1);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(1)
        ->and($context->maximum)->toBe(9);
});
