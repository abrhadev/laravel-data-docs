<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MaxProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new MaxProcessor();
});

it('sets maximum for integer type', function () {
    $testData = new class (50) extends Data {
        public function __construct(
            #[Max(100)]
            public int $score,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'score');

    $context = new ParameterContext('score', $property);
    $context->type = 'integer';

    $attribute = new Max(100);
    $this->processor->process($attribute, $context);

    expect($context->maximum)->toBe(100)
        ->and($context->maxLength)->toBeNull()
        ->and($context->maxItems)->toBeNull()
        ->and($context->descriptions)->toContain('Must be maximum <code>100</code>.');
});

it('sets maxLength for string type', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Max(50)]
            public string $title,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'title');

    $context = new ParameterContext('title', $property);
    $context->type = 'string';

    $attribute = new Max(50);
    $this->processor->process($attribute, $context);

    expect($context->maxLength)->toBe(50)
        ->and($context->maximum)->toBeNull()
        ->and($context->maxItems)->toBeNull()
        ->and($context->descriptions)->toContain('Must have maximum <code>50</code> characters.');
});

it('sets maxItems for array type', function () {
    $testData = new class ([]) extends Data {
        public function __construct(
            #[Max(10)]
            public array $items,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'items');

    $context = new ParameterContext('items', $property);
    $context->type = 'string[]';

    $attribute = new Max(10);
    $this->processor->process($attribute, $context);

    expect($context->maxItems)->toBe(10)
        ->and($context->maximum)->toBeNull()
        ->and($context->maxLength)->toBeNull()
        ->and($context->descriptions)->toContain('Must have maximum <code>10</code> items.');
});
