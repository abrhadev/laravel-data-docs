<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\AttributeProcessingStage;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->stage = new AttributeProcessingStage();
});

it('processes single attribute correctly', function () {
    $testData = new class ('test@example.com') extends Data {
        public function __construct(
            #[Email]
            public string $email,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'email');

    $context = new ParameterContext('email', $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->format)->toBe('email')
        ->and($result->description)->toBe('Must be a string. Must be a valid email address.');
});

it('processes multiple attributes on same property', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Min(3)]
            #[Max(50)]
            public string $username,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'username');

    $context = new ParameterContext('username', $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->minLength)->toBe(3)
        ->and($result->maxLength)->toBe(50)
        ->and($result->description)->toContain('Must be a string.')
        ->and($result->description)->toContain('Must have minimum <code>3</code> characters.')
        ->and($result->description)->toContain('Must have maximum <code>50</code> characters.');
});

it('does not modify context when no attributes', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            public string $plainField,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'plainField');

    $context = new ParameterContext('plainField', $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.')
        ->and($result->format)->toBeNull()
        ->and($result->pattern)->toBeNull();
});

it('returns the same context instance', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            public string $field,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'field');

    $context = new ParameterContext('field', $property);
    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

it('combines descriptions properly with empty base description', function () {
    $testData = new class ('test@example.com') extends Data {
        public function __construct(
            #[Email]
            public string $email,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'email');

    $context = new ParameterContext('email', $property);
    $context->description = '';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a valid email address.');
});
