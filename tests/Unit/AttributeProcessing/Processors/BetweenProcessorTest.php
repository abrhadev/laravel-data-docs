<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\BetweenProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new BetweenProcessor();
});

it('sets minimum and maximum for integer type', function () {
    $testData = new class (50) extends Data {
        public function __construct(
            #[Between(10, 100)]
            public int $score,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'score');

    $context = new ParameterContext('score', $property);
    $context->type = 'integer';

    $attribute = new Between(10, 100);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(100)
        ->and($context->descriptions)->toContain('Must be between <code>10</code> and <code>100</code>.');
});

it('sets minLength and maxLength for string type', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Between(5, 20)]
            public string $description,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'description');

    $context = new ParameterContext('description', $property);
    $context->type = 'string';

    $attribute = new Between(5, 20);
    $this->processor->process($attribute, $context);

    expect($context->minLength)->toBe(5)
        ->and($context->maxLength)->toBe(20)
        ->and($context->descriptions)->toContain('Must have between <code>5</code> and <code>20</code> characters.');
});
