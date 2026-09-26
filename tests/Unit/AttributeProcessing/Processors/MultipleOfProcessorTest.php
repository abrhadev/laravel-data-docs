<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MultipleOfProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

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

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new MultipleOf(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->multipleOf)->toBeNull();
});

it('skips a non-finite divisor it cannot document', function (float $divisor) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new MultipleOf($divisor), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->multipleOf)->toBeNull();
})->with([INF, NAN]);

it('states a divisor multipleOf cannot hold without publishing it', function (float|int $divisor) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new MultipleOf($divisor), $context);

    expect($context->descriptions)->toBe(["Must be a multiple of {$divisor}."])
        ->and($context->multipleOf)->toBeNull();
})->with([0.5, 0.01, -3, 1e20]);

it('publishes an integral float divisor as an int', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new MultipleOf(5.0), $context);

    expect($context->multipleOf)->toBe(5);
});
