<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\LessThanOrEqualTo;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new LessThanOrEqualToProcessor();
});

it('sets maximum and appends description', function () {
    $testData = new class (30) extends Data {
        public function __construct(
            #[LessThanOrEqualTo(50)]
            public int $limit,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'limit');

    $context = new ParameterContext('limit', $property);
    $context->type = 'integer';

    $attribute = new LessThanOrEqualTo(50);
    $this->processor->process($attribute, $context);

    expect($context->maximum)->toBe(50)
        ->and($context->descriptions)->toContain('Must be less than or equal to <code>50</code>.');
});
