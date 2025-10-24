<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new GreaterThanProcessor();
});

it('sets exclusiveMinimum and appends description', function () {
    $testData = new class (10) extends Data {
        public function __construct(
            #[GreaterThan(0)]
            public int $score,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'score');

    $context = new ParameterContext('score', $property);
    $context->type = 'integer';

    $attribute = new GreaterThan(0);
    $this->processor->process($attribute, $context);

    expect($context->exclusiveMinimum)->toBe(0)
        ->and($context->descriptions)->toContain('Must be greater than <code>0</code>.');
});
