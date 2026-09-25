<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Support\Validation\References\FieldReference;
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

it('renders a field reference as a field name, not a value', function () {
    // The only shipped path that can carry a FieldReference. A reference is not
    // numeric, so the constraint field stays null exactly as it did before.
    $context = conditionContext();

    $this->processor->process(new LessThan(new FieldReference('min_price')), $context);

    expect($context->descriptions)->toContain('Must be less than <b><i>min_price</i></b>.')
        ->and($context->exclusiveMaximum)->toBeNull();
});
