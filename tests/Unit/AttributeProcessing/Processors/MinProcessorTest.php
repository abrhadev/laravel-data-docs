<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MinProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

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
