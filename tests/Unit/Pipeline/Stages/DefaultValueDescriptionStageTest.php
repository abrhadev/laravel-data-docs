<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueDescriptionStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueStage;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\DataProperty;

beforeEach(function () {
    $this->stage = new DefaultValueDescriptionStage();
});

it('adds default value description for string', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be a string.';
    $context->default = 'hello';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string. Defaults to <code>hello</code>.');
});

it('adds default value description for integer', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be an integer.';
    $context->default = 42;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an integer. Defaults to <code>42</code>.');
});

it('adds default value description for boolean true', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be a boolean.';
    $context->default = true;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a boolean. Defaults to <code>true</code>.');
});

it('adds default value description for boolean false', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be a boolean.';
    $context->default = false;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a boolean. Defaults to <code>false</code>.');
});

it('adds default value description for array', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be an array.';
    $context->default = ['item1', 'item2'];

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array. Defaults to <code>["item1","item2"]</code>.');
});

it('adds default value description for object', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be an object.';
    $context->default = (object) ['key' => 'value'];

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an object. Defaults to <code>{"key":"value"}</code>.');
});

it('adds default value description for null', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be a string.';
    $context->default = null;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.');
});

it('adds default value description for zero', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be an integer.';
    $context->default = 0;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an integer. Defaults to <code>0</code>.');
});

it('adds default value description for empty string', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be a string.';
    $context->default = '';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string. Defaults to <code></code>.');
});

it('handles empty base description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = '';
    $context->default = 'test';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Defaults to <code>test</code>.');
});

it('does not modify description when default is null', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->description = 'Must be a string.';
    $context->default = null;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.');
});

it('returns the same context instance', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->default = 'test';

    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

it('renders a backed enum default by its case name and backing value', function () {
    $property = app(DataConfig::class)->getDataClass(EnumDefaultDescriptionTestData::class)->properties->first(fn($p) => $p->name === 'status');
    $context = (new DefaultValueStage())->process(new ParameterContext('status', $property));
    $context->description = 'Must be one of: <code>Active</code> (active), <code>Inactive</code> (inactive).';

    $result = $this->stage->process($context);

    expect($result->description)->toBe(
        'Must be one of: <code>Active</code> (active), <code>Inactive</code> (inactive). Defaults to <code>Active</code> (active).'
    );
});

it('renders a pure enum default by its case name', function () {
    $property = app(DataConfig::class)->getDataClass(EnumDefaultDescriptionTestData::class)->properties->first(fn($p) => $p->name === 'role');
    $context = (new DefaultValueStage())->process(new ParameterContext('role', $property));
    $context->description = '';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Defaults to <code>ADMIN</code>.');
});

enum DefaultDescriptionTestBackedEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}

enum DefaultDescriptionTestUnitEnum
{
    case ADMIN;
    case USER;
}

class EnumDefaultDescriptionTestData extends Data
{
    public function __construct(
        public DefaultDescriptionTestBackedEnum $status = DefaultDescriptionTestBackedEnum::Active,
        public DefaultDescriptionTestUnitEnum $role = DefaultDescriptionTestUnitEnum::ADMIN,
    ) {}
}
