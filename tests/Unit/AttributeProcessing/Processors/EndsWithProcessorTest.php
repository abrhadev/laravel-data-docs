<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EndsWithProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\EndsWith;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new EndsWithProcessor();
});

it('sets pattern for single suffix', function () {
    $testData = new class ('valueSuffix') extends Data {
        public function __construct(
            #[EndsWith('suffix')]
            public string $suffixedValue,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'suffixedValue');

    $context = new ParameterContext('suffixedValue', $property);
    $context->type = 'string';

    $attribute = new EndsWith('suffix');
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('(suffix)$')
        ->and($context->descriptions)->toContain('Must end with one of: <code>suffix</code>.');
});

it('sets pattern for multiple suffixes', function () {
    $testData = new class ('image.jpg') extends Data {
        public function __construct(
            #[EndsWith('.jpg', '.png', '.gif')]
            public string $filename,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'filename');

    $context = new ParameterContext('filename', $property);
    $context->type = 'string';

    $attribute = new EndsWith('.jpg', '.png', '.gif');
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('(\.jpg|\.png|\.gif)$')
        ->and($context->descriptions)->toContain('Must end with one of: <code>.jpg</code>, <code>.png</code>, <code>.gif</code>.');
});
