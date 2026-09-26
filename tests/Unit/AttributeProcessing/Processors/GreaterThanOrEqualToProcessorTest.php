<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Support\Validation\References\FieldReference;
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

it('renders a field reference as a field name, not a value', function () {
    // The only shipped path that can carry a FieldReference. A reference is not
    // numeric, so the constraint field stays null exactly as it did before.
    $context = conditionContext();

    $this->processor->process(new GreaterThanOrEqualTo(new FieldReference('min_price')), $context);

    expect($context->descriptions)->toContain('Must be greater than or equal to <b><i>min_price</i></b>.')
        ->and($context->minimum)->toBeNull();
});

it('publishes a fractional bound exactly', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new GreaterThanOrEqualTo(0.5), $context);

    expect($context->minimum)->toBe(0.5)
        ->and($context->descriptions)->toBe(['Must be greater than or equal to <code>0.5</code>.']);
});

it('writes a float bound an int holds exactly', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new GreaterThanOrEqualTo(5.0), $context);

    expect($context->minimum)->toBe(5);
});

it('leaves a bound another attribute set when the operand is a field reference', function () {
    $context = conditionContext();
    $context->type = 'integer';
    $context->minimum = 3;

    $this->processor->process(new GreaterThanOrEqualTo(new FieldReference('other')), $context);

    expect($context->minimum)->toBe(3);
});
