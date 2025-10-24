<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RegexProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->processor = new RegexProcessor();
});

it('sets pattern and appends description', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Regex('/^[a-z]+$/')]
            public string $pattern,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'pattern');

    $context = new ParameterContext('pattern', $property);
    $context->type = 'string';

    $attribute = new Regex('/^[a-z]+$/');
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('/^[a-z]+$/')
        ->and($context->descriptions)->toContain('Must match the regex <code>/^[a-z]+$/</code>.');
});
