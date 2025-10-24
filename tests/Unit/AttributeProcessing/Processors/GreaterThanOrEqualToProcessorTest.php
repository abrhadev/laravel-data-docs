<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new GreaterThanOrEqualToProcessor();
});

it('sets minimum and appends description', function () {
    $testData = new class (10) extends Data {
        public function __construct(
            #[GreaterThanOrEqualTo(5)]
            public int $rating,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'rating');

    $context = new ParameterContext('rating', $property);
    $context->type = 'integer';

    $attribute = new GreaterThanOrEqualTo(5);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(5)
        ->and($context->descriptions)->toContain('Must be greater than or equal to <code>5</code>.');
});
