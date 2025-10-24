<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MultipleOfProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new MultipleOfProcessor();
});

it('sets multipleOf and appends description', function () {
    $testData = new class (10) extends Data {
        public function __construct(
            #[MultipleOf(5)]
            public int $quantity,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'quantity');

    $context = new ParameterContext('quantity', $property);
    $context->type = 'integer';

    $attribute = new MultipleOf(5);
    $this->processor->process($attribute, $context);

    expect($context->multipleOf)->toBe(5)
        ->and($context->descriptions)->toContain('Must be a multiple of 5.');
});
