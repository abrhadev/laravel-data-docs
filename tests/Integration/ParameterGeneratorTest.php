<?php

use Abrha\LaravelDataDocs\Attributes\Hidden;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->dataConfig = app(DataConfig::class);
    $this->pipeline = PipelineFactory::createDefault();
    $this->generator = new ParameterGenerator($this->pipeline, $this->dataConfig);
});

it('generates parameters for simple data class', function () {
    $parameterObjects = ($this->generator)(SimpleTestData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters)->toHaveKey('name')
        ->and($parameters)->toHaveKey('age')
        ->and($parameters['name']['type'])->toBe('string')
        ->and($parameters['name']['required'])->toBeTrue()
        ->and($parameters['age']['type'])->toBe('integer')
        ->and($parameters['age']['required'])->toBeTrue();
});

it('handles optional and nullable properties', function () {
    $parameterObjects = ($this->generator)(OptionalFieldsData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters)->toHaveKey('requiredField')
        ->and($parameters)->toHaveKey('optionalField')
        ->and($parameters)->toHaveKey('nullableField')
        ->and($parameters['requiredField']['required'])->toBeTrue()
        ->and($parameters['nullableField']['required'])->toBeFalse();
});

it('excludes hidden properties from output', function () {
    $parameters = ($this->generator)(HiddenFieldsData::class);

    expect($parameters)->toHaveKey('visibleField')
        ->and($parameters)->not->toHaveKey('hiddenField');
});

it('generates parameters for data class with defaults', function () {
    $parameterObjects = ($this->generator)(DefaultValuesData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters)->toHaveKey('withDefault')
        ->and($parameters)->toHaveKey('withoutDefault')
        ->and($parameters['withoutDefault']['required'])->toBeTrue();
});

it('handles nested data objects', function () {
    $parameterObjects = ($this->generator)(ParentData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters)->toHaveKey('child')
        ->and($parameters)->toHaveKey('child.name')
        ->and($parameters)->toHaveKey('child.age')
        ->and($parameters['child']['type'])->toBe('object')
        ->and($parameters['child.name']['type'])->toBe('string')
        ->and($parameters['child.age']['type'])->toBe('integer');
});

it('handles array of data objects', function () {
    $parameterObjects = ($this->generator)(ArrayData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters)->toHaveKey('items')
        ->and($parameters)->toHaveKey('items[].name')
        ->and($parameters)->toHaveKey('items[].age')
        ->and($parameters['items']['type'])->toBe('object[]')
        ->and($parameters['items[].name']['type'])->toBe('string')
        ->and($parameters['items[].age']['type'])->toBe('integer');
});

it('handles multiple levels of nesting', function () {
    $parameters = ($this->generator)(DeepNestedData::class);

    expect($parameters)->toHaveKey('parent')
        ->and($parameters)->toHaveKey('parent.child')
        ->and($parameters)->toHaveKey('parent.child.name')
        ->and($parameters)->toHaveKey('parent.child.age');
});

it('handles all scalar types', function () {
    $parameterObjects = ($this->generator)(ScalarTypesData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters['stringField']['type'])->toBe('string')
        ->and($parameters['intField']['type'])->toBe('integer')
        ->and($parameters['floatField']['type'])->toBe('number')
        ->and($parameters['boolField']['type'])->toBe('boolean')
        ->and($parameters['arrayField']['type'])->toBe('[]');
});

it('returns empty array for non-existent class', function () {
    $parameters = ($this->generator)('NonExistentClass');

    expect($parameters)->toBe([]);
});

it('sets location to body for all parameters', function () {
    $parameters = ($this->generator)(SimpleTestData::class);

    expect($parameters['name'])->toHaveKey('name')
        ->and($parameters['age'])->toHaveKey('name');
});

it('handles hidden properties in nested objects', function () {
    $parameters = ($this->generator)(NestedHiddenData::class);

    expect($parameters)->toHaveKey('nested')
        ->and($parameters)->toHaveKey('nested.visibleField')
        ->and($parameters)->not->toHaveKey('nested.hiddenField');
});

class SimpleTestData extends Data
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}
}

class OptionalFieldsData extends Data
{
    public function __construct(
        public string $requiredField,
        public ?string $nullableField,
        public string $optionalField = 'default',
    ) {}
}

class HiddenFieldsData extends Data
{
    public function __construct(
        public string $visibleField,
        #[Hidden]
        public string $hiddenField,
    ) {}
}

class DefaultValuesData extends Data
{
    public function __construct(
        public string $withoutDefault,
        public string $withDefault = 'default value',
    ) {}
}

class ChildData extends Data
{
    public function __construct(
        public string $name,
        public int $age,
    ) {}
}

class ParentData extends Data
{
    public function __construct(
        public ChildData $child,
    ) {}
}

class ArrayData extends Data
{
    public function __construct(
        /** @var array<ChildData> */
        public array $items,
    ) {}
}

class DeepNestedData extends Data
{
    public function __construct(
        public ParentData $parent,
    ) {}
}

class ScalarTypesData extends Data
{
    public function __construct(
        public string $stringField,
        public int $intField,
        public float $floatField,
        public bool $boolField,
        public array $arrayField,
    ) {}
}

class NestedWithHiddenData extends Data
{
    public function __construct(
        public string $visibleField,
        #[Hidden]
        public string $hiddenField,
    ) {}
}

class NestedHiddenData extends Data
{
    public function __construct(
        public NestedWithHiddenData $nested,
    ) {}
}
