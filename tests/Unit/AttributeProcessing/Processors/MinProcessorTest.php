<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MinProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new MinProcessor();
});

it('sets minimum for integer type', function () {
    $testData = new class (18) extends Data {
        public function __construct(
            #[Min(5)]
            public int $age,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'age');

    $context = new ParameterContext('age', $property);
    $context->type = 'integer';

    $attribute = new Min(5);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(5)
        ->and($context->minLength)->toBeNull()
        ->and($context->minItems)->toBeNull()
        ->and($context->descriptions)->toContain('Must be minimum <code>5</code>.');
});

it('sets minLength for string type', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Min(3)]
            public string $name,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'name');

    $context = new ParameterContext('name', $property);
    $context->type = 'string';

    $attribute = new Min(3);
    $this->processor->process($attribute, $context);

    expect($context->minLength)->toBe(3)
        ->and($context->minimum)->toBeNull()
        ->and($context->minItems)->toBeNull()
        ->and($context->descriptions)->toContain('Must have minimum <code>3</code> characters.');
});

it('sets minItems for array type', function () {
    $testData = new class ([]) extends Data {
        public function __construct(
            #[Min(2)]
            public array $items,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'items');

    $context = new ParameterContext('items', $property);
    $context->type = 'string[]';

    $attribute = new Min(2);
    $this->processor->process($attribute, $context);

    expect($context->minItems)->toBe(2)
        ->and($context->minimum)->toBeNull()
        ->and($context->minLength)->toBeNull()
        ->and($context->descriptions)->toContain('Must have minimum <code>2</code> items.');
});

it('writes an integral float as an int', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Min(5.0), $context);

    expect($context->minimum)->toBe(5);
});

it('writes a fractional length bound as the whole number it means', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Min(2.5), $context);

    expect($context->minLength)->toBe(3)
        ->and($context->minimum)->toBeNull()
        ->and($context->descriptions)->toBe(['Must have minimum <code>2.5</code> characters.']);
});

it('writes a fractional item-count bound as the whole number it means', function () {
    $context = conditionContext();
    $context->type = 'string[]';

    $this->processor->process(new Min(1.2), $context);

    expect($context->minItems)->toBe(2);
});

it('writes no negative length bound', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Min(-1), $context);

    expect($context->minLength)->toBeNull();
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new Min(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->minLength)->toBeNull()
        ->and($context->minItems)->toBeNull();
});

it('skips a non-finite bound it cannot document', function (float $bound) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Min($bound), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
})->with([INF, -INF, NAN]);

it('publishes a numeric bound an int cannot hold exactly', function (float $bound) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Min($bound), $context);

    expect($context->descriptions)->toHaveCount(1)
        ->and($context->descriptions[0])->toContain("<code>{$bound}</code>")
        ->and($context->minimum)->toBe($bound)
        ->and($context->maximum)->toBeNull();
})->with([0.5, 1e20]);
