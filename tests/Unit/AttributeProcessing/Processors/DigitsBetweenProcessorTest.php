<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsBetweenProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new DigitsBetweenProcessor();
});

it('sets minimum and maximum for digit range', function () {
    $testData = new class ('12345') extends Data {
        public function __construct(
            #[DigitsBetween(2, 8)]
            public string $accountNumber,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'accountNumber');

    $context = new ParameterContext('accountNumber', $property);
    $context->type = 'string';

    $attribute = new DigitsBetween(2, 8);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(99999999)
        ->and($context->descriptions)->toContain('Must have between <code>2</code> and <code>8</code> digits.');
});
