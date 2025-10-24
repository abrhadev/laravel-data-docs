<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SizeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new SizeProcessor();
});

it('sets exact size for integer type', function () {
    $testData = new class (10) extends Data {
        public function __construct(
            #[Size(10)]
            public int $value,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'value');

    $context = new ParameterContext('value', $property);
    $context->type = 'integer';

    $attribute = new Size(10);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(10)
        ->and($context->descriptions)->toContain('Must be exactly <code>10</code>.');
});

it('sets exact size for string type', function () {
    $testData = new class ('123456') extends Data {
        public function __construct(
            #[Size(6)]
            public string $verificationCode,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'verificationCode');

    $context = new ParameterContext('verificationCode', $property);
    $context->type = 'string';

    $attribute = new Size(6);
    $this->processor->process($attribute, $context);

    expect($context->minLength)->toBe(6)
        ->and($context->maxLength)->toBe(6)
        ->and($context->descriptions)->toContain('Must have exactly <code>6</code> characters.');
});

it('sets exact size for array type', function () {
    $testData = new class ([1, 2, 3]) extends Data {
        public function __construct(
            #[Size(3)]
            public array $items,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'items');

    $context = new ParameterContext('items', $property);
    $context->type = 'integer[]';

    $attribute = new Size(3);
    $this->processor->process($attribute, $context);

    expect($context->minItems)->toBe(3)
        ->and($context->maxItems)->toBe(3)
        ->and($context->descriptions)->toContain('Must have exactly <code>3</code> items.');
});
