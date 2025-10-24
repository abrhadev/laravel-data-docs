<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\ExampleGenerationStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $faker = \Faker\Factory::create();
    $faker->seed(1234);
    $this->stage = new ExampleGenerationStage($faker);
    $this->dataConfig = app(DataConfig::class);
});

it('does not override manually set example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->example = 'manual example';

    $result = $this->stage->process($context);

    expect($result->example)->toBe('manual example');
});

it('skips object types', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'object';

    $result = $this->stage->process($context);

    expect($result->example)->toBeNull();
});

it('skips object array types', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'object[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeNull();
});

it('generates example from enum values', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE]
    );

    $result = $this->stage->process($context);

    expect($result->example)->toBe('inactive');
});

it('generates array of unique enum values for enum array type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE, ExampleTestEnum::PENDING]
    );

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and(count($result->example))->toBeGreaterThan(0)
        ->and(count($result->example))->toBeLessThanOrEqual(3)
        ->and($result->example)->each->toBeIn(['active', 'inactive', 'pending'])
        ->and($result->example)->toBe(array_unique($result->example));
});

it('respects minItems constraint for enum arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->minItems = 2;
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE, ExampleTestEnum::PENDING]
    );

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeGreaterThanOrEqual(2);
});

it('respects maxItems constraint for enum arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->maxItems = 1;
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE, ExampleTestEnum::PENDING]
    );

    $result = $this->stage->process($context);

    expect(count($result->example))->toBe(1);
});

it('caps enum array count at total number of enum values', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->minItems = 5;
    $context->maxItems = 10;
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE]
    );

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeLessThanOrEqual(2)
        ->and($result->example)->toBe(array_unique($result->example));
});

it('returns empty array for enum array with no values', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: []
    );

    $result = $this->stage->process($context);

    expect($result->example)->toBe([]);
});

it('generates example for string type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and(strlen($result->example))->toBeGreaterThan(0);
});

it('generates email format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'email';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[^@]+@[^@]+\.[^@]+$/');
});

it('generates url format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'url';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^https?:\/\/.+/');
});

it('generates uuid format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'uuid';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('generates ipv4 format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'ipv4';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/');
});

it('generates ipv6 format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'ipv6';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[0-9a-f:]+$/i');
});

it('generates date format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'date';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
});

it('generates date-time format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'date-time';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

it('generates time format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'time';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{2}:\d{2}:\d{2}$/');
});

it('generates password format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'password';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and(strlen($result->example))->toBeGreaterThanOrEqual(12)
        ->and(strlen($result->example))->toBeLessThanOrEqual(20);
});

it('generates example matching pattern', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->pattern = '[A-Z]{3}-\d{4}';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[A-Z]{3}-\d{4}$/');
});

it('ignores length constraints when pattern is set', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->pattern = '[A-Z]{2}';
    $context->minLength = 10;
    $context->maxLength = 20;

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[A-Z]{2}$/')
        ->and(strlen($result->example))->toBe(2);
});

it('prioritizes format over pattern', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'email';
    $context->pattern = '[A-Z]{5}';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[^@]+@[^@]+\.[^@]+$/')
        ->and($result->example)->not->toMatch('/^[A-Z]{5}$/');
});

it('respects minLength constraint', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->minLength = 20;

    $result = $this->stage->process($context);

    expect(strlen($result->example))->toBeGreaterThanOrEqual(20);
});

it('respects maxLength constraint', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->maxLength = 3;

    $result = $this->stage->process($context);

    expect(strlen($result->example))->toBeLessThanOrEqual(3);
});

it('generates example for integer type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';

    $result = $this->stage->process($context);

    expect($result->example)->toBeInt()
        ->and($result->example)->toBeGreaterThanOrEqual(1)
        ->and($result->example)->toBeLessThanOrEqual(100);
});

it('respects minimum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 100;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThanOrEqual(100);
});

it('respects maximum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->maximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeLessThanOrEqual(10);
});

it('respects both min and max constraints for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 5;
    $context->maximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThanOrEqual(5)
        ->and($result->example)->toBeLessThanOrEqual(10);
});

it('respects exclusiveMinimum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->exclusiveMinimum = 5;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThan(5);
});

it('respects exclusiveMaximum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->exclusiveMaximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeLessThan(10);
});

it('respects multipleOf constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->multipleOf = 5;

    $result = $this->stage->process($context);

    expect($result->example % 5)->toBe(0);
});

it('generates example for number type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestFloatData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number';

    $result = $this->stage->process($context);

    expect($result->example)->toBeFloat()
        ->and($result->example)->toBeGreaterThanOrEqual(1.0)
        ->and($result->example)->toBeLessThanOrEqual(100.0);
});

it('respects minimum constraint for numbers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestFloatData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number';
    $context->minimum = 100.5;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThanOrEqual(100.5);
});

it('generates example for boolean type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestBoolData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'boolean';

    $result = $this->stage->process($context);

    expect($result->example)->toBeBool();
});

it('generates example for string array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and(count($result->example))->toBeGreaterThan(0)
        ->and(count($result->example))->toBeLessThanOrEqual(3)
        ->and($result->example[0])->toBeString();
});

it('generates example for integer array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and($result->example[0])->toBeInt();
});

it('generates example for number array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and($result->example[0])->toBeFloat();
});

it('generates example for boolean array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'boolean[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and($result->example[0])->toBeBool();
});

it('respects minItems constraint for arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->minItems = 2;

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeGreaterThanOrEqual(2);
});

it('respects maxItems constraint for arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->maxItems = 1;

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeLessThanOrEqual(1);
});

class ExampleTestStringData extends Data
{
    public function __construct(
        public string $field,
    ) {}
}

class ExampleTestIntData extends Data
{
    public function __construct(
        public int $number,
    ) {}
}

class ExampleTestFloatData extends Data
{
    public function __construct(
        public float $amount,
    ) {}
}

class ExampleTestBoolData extends Data
{
    public function __construct(
        public bool $flag,
    ) {}
}

class ExampleTestArrayData extends Data
{
    public function __construct(
        public array $items,
    ) {}
}

enum ExampleTestEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}
