<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new LessThanProcessor();
});

it('sets exclusiveMaximum and appends description', function () {
    $testData = new class (50) extends Data {
        public function __construct(
            #[LessThan(100)]
            public int $percentage,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'percentage');

    $context = new ParameterContext('percentage', $property);
    $context->type = 'integer';

    $attribute = new LessThan(100);
    $this->processor->process($attribute, $context);

    expect($context->exclusiveMaximum)->toBe(100)
        ->and($context->descriptions)->toContain('Must be less than <code>100</code>.');
});
