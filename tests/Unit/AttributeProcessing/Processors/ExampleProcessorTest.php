<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExampleProcessor;
use Abrha\LaravelDataDocs\Attributes\Example;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new ExampleProcessor();
});

it('sets example value from attribute', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Example('example value')]
            public string $field,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'field');

    $context = new ParameterContext('field', $property);

    $attribute = new Example('example value');
    $this->processor->process($attribute, $context);

    expect($context->example)->toBe('example value');
});

it('handles integer example values', function () {
    $testData = new class (42) extends Data {
        public function __construct(
            #[Example(42)]
            public int $number,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'number');

    $context = new ParameterContext('number', $property);

    $attribute = new Example(42);
    $this->processor->process($attribute, $context);

    expect($context->example)->toBe(42);
});

it('handles array example values', function () {
    $testData = new class ([]) extends Data {
        public function __construct(
            #[Example(['a', 'b', 'c'])]
            public array $items,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'items');

    $context = new ParameterContext('items', $property);

    $attribute = new Example(['a', 'b', 'c']);
    $this->processor->process($attribute, $context);

    expect($context->example)->toBe(['a', 'b', 'c']);
});

it('handles boolean example values', function () {
    $testData = new class (true) extends Data {
        public function __construct(
            #[Example(false)]
            public bool $flag,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'flag');

    $context = new ParameterContext('flag', $property);

    $attribute = new Example(false);
    $this->processor->process($attribute, $context);

    expect($context->example)->toBeFalse();
});
