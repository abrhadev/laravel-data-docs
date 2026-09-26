<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\StartsWithProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

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

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new StartsWith(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->pattern)->toBeNull();
});

it('publishes a note and no pattern when no non-empty prefix is given', function (StartsWith $attribute) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->descriptions)->toBe(['Note: must start with a value, but none is given, so any request that sends this field fails validation.'])
        ->and($context->pattern)->toBeNull();
})->with([
    'no value'     => fn() => new StartsWith(),
    'empty values' => fn() => new StartsWith('', ''),
]);

it('reads the prefixes as Laravel does: split at commas, empty ones skipped', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new StartsWith('a,b', '', 'a'), $context);

    expect($context->pattern)->toBe('^(a|b)')
        ->and($context->descriptions)->toBe(['Must start with one of: <code>a</code>, <code>b</code>.']);
});
