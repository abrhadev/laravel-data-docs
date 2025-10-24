<?php

use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessor;
use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessorRegistry;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\CustomTypeStage;
use Abrha\LaravelDataDocs\ValueObjects\CustomTypeConfig;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->dataConfig = app(DataConfig::class);
});

it('skips standard string type', function () {
    $stage = new CustomTypeStage();
    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $result = $stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->descriptions)->toBe([]);
});

it('skips standard integer type', function () {
    $stage = new CustomTypeStage();
    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $result = $stage->process($context);

    expect($result->type)->toBe('integer')
        ->and($result->descriptions)->toBe([]);
});

it('skips standard array types', function () {
    $stage = new CustomTypeStage();
    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $result = $stage->process($context);

    expect($result->type)->toBe('string[]')
        ->and($result->descriptions)->toBe([]);
});

it('skips object type', function () {
    $stage = new CustomTypeStage();
    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'object';
    $result = $stage->process($context);

    expect($result->type)->toBe('object')
        ->and($result->descriptions)->toBe([]);
});

it('applies config-based type mapping', function () {
    $stage = new CustomTypeStage([
        'CustomTestClass' => new CustomTypeConfig(
            type: 'string',
            descriptions: ['Must be a custom value']
        ),
    ]);

    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'CustomTestClass';
    $result = $stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->descriptions)->toBe(['Must be a custom value']);
});

it('applies config-based type with pattern', function () {
    $stage = new CustomTypeStage([
        'TimestampClass' => new CustomTypeConfig(
            type: 'number',
            descriptions: ['Must be a timestamp'],
            pattern: '^\d{12,13}$'
        ),
    ]);

    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'TimestampClass';
    $result = $stage->process($context);

    expect($result->type)->toBe('number')
        ->and($result->pattern)->toBe('^\d{12,13}$')
        ->and($result->descriptions)->toBe(['Must be a timestamp']);
});

it('applies config-based type with all constraint fields', function () {
    $stage = new CustomTypeStage([
        'ComplexClass' => new CustomTypeConfig(
            type: 'string',
            descriptions: ['Complex type'],
            pattern: '^[A-Z]+$',
            format: 'custom-format',
            minimum: 10,
            maximum: 100,
            exclusiveMinimum: 9,
            exclusiveMaximum: 101,
            minLength: 5,
            maxLength: 50,
            minItems: 1,
            maxItems: 10,
            multipleOf: 5
        ),
    ]);

    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'ComplexClass';
    $result = $stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->pattern)->toBe('^[A-Z]+$')
        ->and($result->format)->toBe('custom-format')
        ->and($result->minimum)->toBe(10)
        ->and($result->maximum)->toBe(100)
        ->and($result->exclusiveMinimum)->toBe(9)
        ->and($result->exclusiveMaximum)->toBe(101)
        ->and($result->minLength)->toBe(5)
        ->and($result->maxLength)->toBe(50)
        ->and($result->minItems)->toBe(1)
        ->and($result->maxItems)->toBe(10)
        ->and($result->multipleOf)->toBe(5)
        ->and($result->descriptions)->toBe(['Complex type']);
});

it('merges descriptions from config with existing descriptions', function () {
    $stage = new CustomTypeStage([
        'TestClass' => new CustomTypeConfig(
            type: 'string',
            descriptions: ['Config description']
        ),
    ]);

    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'TestClass';
    $context->descriptions = ['Existing description'];
    $result = $stage->process($context);

    expect($result->descriptions)->toBe(['Existing description', 'Config description']);
});

it('uses processor when registered', function () {
    $stage = new CustomTypeStage();

    $processor = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'number';
            $context->pattern = '^[0-9]+$';
            $context->descriptions[] = 'Processed by custom processor';
        }
    };

    CustomTypeProcessorRegistry::getInstance()->register('ProcessorTestClass', $processor);

    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'ProcessorTestClass';
    $result = $stage->process($context);

    expect($result->type)->toBe('number')
        ->and($result->pattern)->toBe('^[0-9]+$')
        ->and($result->descriptions)->toBe(['Processed by custom processor']);
});

it('falls back to default string type with generic description', function () {
    $stage = new CustomTypeStage();
    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'UnknownCustomClass';
    $result = $stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->descriptions)->toBe(['Must be a UnknownCustomClass']);
});

it('returns same context instance', function () {
    $stage = new CustomTypeStage();
    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'SomeClass';
    $result = $stage->process($context);

    expect($result)->toBe($context);
});

it('config takes precedence over processor', function () {
    $stage = new CustomTypeStage([
        'PrecedenceClass' => new CustomTypeConfig(
            type: 'string',
            descriptions: ['From config']
        ),
    ]);

    $processor = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'number';
            $context->descriptions[] = 'From processor';
        }
    };

    CustomTypeProcessorRegistry::getInstance()->register('PrecedenceClass', $processor);

    $dataClass = $this->dataConfig->getDataClass(CustomTypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'PrecedenceClass';
    $result = $stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->descriptions)->toBe(['From config']);
});

class CustomTypeTestStringData extends Data
{
    public function __construct(public string $test) {}
}
