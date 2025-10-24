<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\StartsWithProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new StartsWithProcessor();
});

it('sets pattern for single prefix', function () {
    $testData = new class ('prefixValue') extends Data {
        public function __construct(
            #[StartsWith('prefix')]
            public string $prefixedValue,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'prefixedValue');

    $context = new ParameterContext('prefixedValue', $property);
    $context->type = 'string';

    $attribute = new StartsWith('prefix');
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('^(prefix)')
        ->and($context->descriptions)->toContain('Must start with one of: <code>prefix</code>.');
});

it('sets pattern for multiple prefixes', function () {
    $testData = new class ('http://example.com') extends Data {
        public function __construct(
            #[StartsWith('http://', 'https://')]
            public string $url,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'url');

    $context = new ParameterContext('url', $property);
    $context->type = 'string';

    $attribute = new StartsWith('http://', 'https://');
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('^(http\:\/\/|https\:\/\/)')
        ->and($context->descriptions)->toContain('Must start with one of: <code>http://</code>, <code>https://</code>.');
});
