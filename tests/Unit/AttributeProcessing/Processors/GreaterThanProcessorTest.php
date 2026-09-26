<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Support\Validation\References\FieldReference;
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

it('renders a field reference as a field name, not a value', function () {
    // The only shipped path that can carry a FieldReference. A reference is not
    // numeric, so the constraint field stays null exactly as it did before.
    $context = conditionContext();

    $this->processor->process(new GreaterThan(new FieldReference('min_price')), $context);

    expect($context->descriptions)->toContain('Must be greater than <b><i>min_price</i></b>.')
        ->and($context->exclusiveMinimum)->toBeNull();
});

it('publishes a fractional bound exactly', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new GreaterThan(0.5), $context);

    expect($context->exclusiveMinimum)->toBe(0.5)
        ->and($context->descriptions)->toBe(['Must be greater than <code>0.5</code>.']);
});

it('tightens fractional bounds like integral ones', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new GreaterThan(0.5), $context);
    $this->processor->process(new GreaterThan(0.25), $context);

    expect($context->exclusiveMinimum)->toBe(0.5);
});

it('writes a float bound an int holds exactly', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new GreaterThan(5.0), $context);

    expect($context->exclusiveMinimum)->toBe(5);
});

it('leaves a bound another attribute set when the operand is a field reference', function () {
    $context = conditionContext();
    $context->type = 'integer';
    $context->exclusiveMinimum = 3;

    $this->processor->process(new GreaterThan(new FieldReference('other')), $context);

    expect($context->exclusiveMinimum)->toBe(3);
});

it('documents nothing for a non-finite bound, which Laravel reads as a field name', function (string $processor, string $attribute, float $bound, string $type) {
    $context = conditionContext();
    $context->type = $type;

    (new $processor())->process(new $attribute($bound), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->numericString)->toBeFalse()
        ->and([$context->minimum, $context->maximum, $context->exclusiveMinimum, $context->exclusiveMaximum])->toBe([null, null, null, null]);
})->with([
    'gt INF on an integer'  => [GreaterThanProcessor::class, GreaterThan::class, INF, 'integer'],
    'gt NAN on a string'    => [GreaterThanProcessor::class, GreaterThan::class, NAN, 'string'],
    'gte -INF on a number'  => [Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanOrEqualToProcessor::class, Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo::class, -INF, 'number'],
    'lt INF on an array'    => [Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanProcessor::class, Spatie\LaravelData\Attributes\Validation\LessThan::class, INF, 'string[]'],
    'lte INF on an integer' => [Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanOrEqualToProcessor::class, Spatie\LaravelData\Attributes\Validation\LessThanOrEqualTo::class, INF, 'integer'],
]);
