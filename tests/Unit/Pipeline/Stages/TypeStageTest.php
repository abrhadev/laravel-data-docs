<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\TypeStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->dataConfig = app(DataConfig::class);
    $this->stage = new TypeStage();
});

it('extracts type name from string property', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('string');
});

it('handles integer type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('integer');
});

it('handles boolean type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestBoolData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('boolean');
});

it('handles float type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestFloatData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('number');
});

it('handles array type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('[]');
});

it('handles typed array of integers', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestIntArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('integer[]');
});

it('handles typed array of strings', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestStringArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('string[]');
});

it('handles typed array of floats', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestFloatArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('number[]');
});

it('handles typed array of booleans', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestBoolArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('boolean[]');
});

it('handles backed enum with integer backing type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestIntBackedEnumData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('integer')
        ->and($result->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($result->enumInfo->enumType)->toBe(EnumType::INT_BACKED)
        ->and($result->enumInfo->cases)->toBe(TypeTestIntBackedEnum::cases());
});

it('handles backed enum with string backing type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestStringBackedEnumData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($result->enumInfo->enumType)->toBe(EnumType::STRING_BACKED)
        ->and($result->enumInfo->cases)->toBe(TypeTestStringBackedEnum::cases());
});

it('handles pure enum', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestUnitEnumData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($result->enumInfo->enumType)->toBe(EnumType::PURE)
        ->and($result->enumInfo->cases)->toBe(TypeTestUnitEnum::cases());
});

it('handles array of backed enums with integer backing', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestIntBackedEnumArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('integer[]')
        ->and($result->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($result->enumInfo->enumType)->toBe(EnumType::INT_BACKED)
        ->and($result->enumInfo->cases)->toBe(TypeTestIntBackedEnum::cases());
});

it('handles array of backed enums with string backing', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestStringBackedEnumArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('string[]')
        ->and($result->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($result->enumInfo->enumType)->toBe(EnumType::STRING_BACKED)
        ->and($result->enumInfo->cases)->toBe(TypeTestStringBackedEnum::cases());
});

it('handles array of unit enums', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestUnitEnumArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('string[]')
        ->and($result->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($result->enumInfo->enumType)->toBe(EnumType::PURE)
        ->and($result->enumInfo->cases)->toBe(TypeTestUnitEnum::cases());
});

it('returns same context instance', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

it('handles custom class type', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestCustomClassData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->type)->toBe('CustomClass');
});

it('only modifies type and enumInfo without affecting other properties', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->required = true;
    $context->nullable = false;
    $context->description = 'Test description';

    $result = $this->stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->required)->toBeTrue()
        ->and($result->nullable)->toBeFalse()
        ->and($result->description)->toBe('Test description');
});

it('marks context as nested object when type is DataObject', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestNestedObjectData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'user');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->hasNestedParameters)->toBeTrue()
        ->and($result->hasArrayParameters)->toBeFalse();
});

it('marks context as array when type is DataArray', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestNestedObjectData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'users');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->hasArrayParameters)->toBeTrue()
        ->and($result->hasNestedParameters)->toBeFalse();
});

it('does not mark context as nested or array for regular types', function () {
    $dataClass = $this->dataConfig->getDataClass(TypeTestNestedObjectData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'name');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->hasNestedParameters)->toBeFalse()
        ->and($result->hasArrayParameters)->toBeFalse();
});

enum TypeTestIntBackedEnum: int
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
}

enum TypeTestStringBackedEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

enum TypeTestUnitEnum
{
    case RED;
    case GREEN;
    case BLUE;
}

class TypeTestStringData extends Data
{
    public function __construct(public string $test) {}
}

class TypeTestIntData extends Data
{
    public function __construct(public int $age) {}
}

class TypeTestBoolData extends Data
{
    public function __construct(public bool $active) {}
}

class TypeTestFloatData extends Data
{
    public function __construct(public float $price) {}
}

class TypeTestArrayData extends Data
{
    public function __construct(public array $tags) {}
}

class TypeTestIntArrayData extends Data
{
    /**
     * @param int[] $numbers
     */
    public function __construct(public array $numbers) {}
}

class TypeTestStringArrayData extends Data
{
    /**
     * @param string[] $tags
     */
    public function __construct(public array $tags) {}
}

class TypeTestFloatArrayData extends Data
{
    /**
     * @param float[] $prices
     */
    public function __construct(public array $prices) {}
}

class TypeTestBoolArrayData extends Data
{
    /**
     * @param bool[] $flags
     */
    public function __construct(public array $flags) {}
}

class TypeTestIntBackedEnumData extends Data
{
    public function __construct(public TypeTestIntBackedEnum $status) {}
}

class TypeTestStringBackedEnumData extends Data
{
    public function __construct(public TypeTestStringBackedEnum $status) {}
}

class TypeTestUnitEnumData extends Data
{
    public function __construct(public TypeTestUnitEnum $color) {}
}

class TypeTestIntBackedEnumArrayData extends Data
{
    /**
     * @param TypeTestIntBackedEnum[] $statuses
     */
    public function __construct(public array $statuses) {}
}

class TypeTestStringBackedEnumArrayData extends Data
{
    /**
     * @param TypeTestStringBackedEnum[] $statuses
     */
    public function __construct(public array $statuses) {}
}

class TypeTestUnitEnumArrayData extends Data
{
    /**
     * @param TypeTestUnitEnum[] $colors
     */
    public function __construct(public array $colors) {}
}

class CustomClass
{
    public function __construct(public string $value) {}
}

class TypeTestCustomClassData extends Data
{
    public function __construct(public CustomClass $custom) {}
}

class TypeTestUserData extends Data
{
    public function __construct(public string $name) {}
}

class TypeTestNestedObjectData extends Data
{
    public function __construct(
        public string $name,
        public TypeTestUserData $user,
        /** @var array<TypeTestUserData> */
        public array $users,
    ) {}
}
