<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\LessThanOrEqualTo;
use Spatie\LaravelData\Support\Validation\References\FieldReference;
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

it('renders a field reference as a field name, not a value', function () {
    // The only shipped path that can carry a FieldReference. A reference is not
    // numeric, so the constraint field stays null exactly as it did before.
    $context = conditionContext();

    $this->processor->process(new LessThanOrEqualTo(new FieldReference('min_price')), $context);

    expect($context->descriptions)->toContain('Must be less than or equal to <b><i>min_price</i></b>.')
        ->and($context->maximum)->toBeNull();
});
