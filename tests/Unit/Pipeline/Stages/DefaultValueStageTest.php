<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueStage;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->dataConfig = app(DataConfig::class);
    $this->stage = new DefaultValueStage();
});

it('preserves default value when property has hasDefaultValue set', function () {
    $dataClass = $this->dataConfig->getDataClass(DefaultValueTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'name');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result)->toBeInstanceOf(\Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext::class);
});

it('does not set default when hasDefaultValue is false', function () {
    $dataClass = $this->dataConfig->getDataClass(DefaultValueTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'required');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->default)->toBeNull();
});

it('returns context with same property', function () {
    $dataClass = $this->dataConfig->getDataClass(DefaultValueTestData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

it('handles BackedEnum default values by extracting value', function () {
    $dataClass = $this->dataConfig->getDataClass(EnumDefaultValueTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'status');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->default)->toBe('active');
});

it('handles UnitEnum default values by extracting name', function () {
    $dataClass = $this->dataConfig->getDataClass(EnumDefaultValueTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'role');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->default)->toBe('ADMIN');
});

enum DefaultValueTestBackedEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum DefaultValueTestUnitEnum
{
    case ADMIN;
    case USER;
}

class DefaultValueTestData extends Data
{
    public function __construct(
        public string $required,
        public string $name = 'default string',
    ) {}
}

class EnumDefaultValueTestData extends Data
{
    public function __construct(
        public DefaultValueTestBackedEnum $status = DefaultValueTestBackedEnum::Active,
        public DefaultValueTestUnitEnum $role = DefaultValueTestUnitEnum::ADMIN,
    ) {}
}
