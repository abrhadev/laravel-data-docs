<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Spatie\LaravelData\Support\DataProperty;

enum ContextTestIntBackedEnum: int
{
    case EIGHTEEN = 18;
    case TWENTY_FIVE = 25;
    case THIRTY = 30;
}

enum ContextTestStringBackedEnum: string
{
    case A = 'a';
    case B = 'b';
}

it('creates context with required properties', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('testName', $property);

    expect($context->name)->toBe('testName')
        ->and($context->property)->toBe($property)
        ->and($context->isHidden)->toBeFalse()
        ->and($context->hasNestedParameters)->toBeFalse()
        ->and($context->hasArrayParameters)->toBeFalse()
        ->and($context->type)->toBeNull()
        ->and($context->required)->toBeNull()
        ->and($context->location)->toBeNull()
        ->and($context->description)->toBe('')
        ->and($context->example)->toBeNull()
        ->and($context->enumInfo)->toBeNull()
        ->and($context->default)->toBeNull();
});

it('converts to Parameter with all properties set', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('age', $property);
    $context->type = 'int';
    $context->required = true;
    $context->location = ParameterLocation::BODY;
    $context->description = 'User age';
    $context->example = 25;
    $context->enumInfo = new EnumInfo(EnumType::INT_BACKED, ContextTestIntBackedEnum::cases());
    $context->default = 20;
    $context->minimum = 18;
    $context->maximum = 100;

    $parameter = $context->toParameter();

    expect($parameter)->toBeInstanceOf(Parameter::class)
        ->and($parameter->name)->toBe('age')
        ->and($parameter->type)->toBe('int')
        ->and($parameter->required)->toBeTrue()
        ->and($parameter->location)->toBe(ParameterLocation::BODY)
        ->and($parameter->description)->toBe('User age')
        ->and($parameter->example)->toBe(25)
        ->and($parameter->enumValues)->toBe([18, 25, 30])
        ->and($parameter->openApiAttributes)->toBe(['default' => 20, 'minimum' => 18, 'maximum' => 100])
        ->and($parameter->openApiAttributes['default'])->toBe(20);
});

it('converts to Parameter with minimal properties', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('name', $property);

    $parameter = $context->toParameter();

    expect($parameter)->toBeInstanceOf(Parameter::class)
        ->and($parameter->name)->toBe('name')
        ->and($parameter->type)->toBe('string')
        ->and($parameter->required)->toBeFalse()
        ->and($parameter->location)->toBe(ParameterLocation::BODY)
        ->and($parameter->description)->toBe('')
        ->and($parameter->example)->toBeNull()
        ->and($parameter->enumValues)->toBeNull()
        ->and($parameter->openApiAttributes)->toBe([]);
});

it('uses default type when type is null', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('field', $property);
    $context->type = null;

    $parameter = $context->toParameter();

    expect($parameter->type)->toBe('string');
});

it('uses default required when required is null', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('field', $property);
    $context->required = null;

    $parameter = $context->toParameter();

    expect($parameter->required)->toBeFalse();
});

it('uses default location when location is null', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('field', $property);
    $context->location = null;

    $parameter = $context->toParameter();

    expect($parameter->location)->toBe(ParameterLocation::BODY);
});

it('preserves query location when set', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('search', $property);
    $context->location = ParameterLocation::QUERY;

    $parameter = $context->toParameter();

    expect($parameter->location)->toBe(ParameterLocation::QUERY);
});

it('allows setting and modifying all properties', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('test', $property);

    $context->isHidden = true;
    $context->hasNestedParameters = true;
    $context->hasArrayParameters = true;
    $context->type = 'custom';
    $context->required = false;
    $context->location = ParameterLocation::QUERY;
    $context->description = 'Modified';
    $context->example = 'example';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ContextTestStringBackedEnum::cases());
    $context->default = 'default';
    $context->format = 'email';
    $context->pattern = '^[a-z]+$';

    expect($context->isHidden)->toBeTrue()
        ->and($context->hasNestedParameters)->toBeTrue()
        ->and($context->hasArrayParameters)->toBeTrue()
        ->and($context->type)->toBe('custom')
        ->and($context->required)->toBeFalse()
        ->and($context->location)->toBe(ParameterLocation::QUERY)
        ->and($context->description)->toBe('Modified')
        ->and($context->example)->toBe('example')
        ->and($context->enumInfo)->toBeInstanceOf(EnumInfo::class)
        ->and($context->enumInfo->enumType)->toBe(EnumType::STRING_BACKED)
        ->and($context->enumInfo->cases)->toBe(ContextTestStringBackedEnum::cases())
        ->and($context->default)->toBe('default')
        ->and($context->format)->toBe('email')
        ->and($context->pattern)->toBe('^[a-z]+$');
});

it('includes all openApiAttributes when converting to Parameter', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('price', $property);
    $context->default = 99.99;
    $context->format = 'double';
    $context->minimum = 0;
    $context->maximum = 1000;
    $context->exclusiveMinimum = -1;
    $context->exclusiveMaximum = 1001;
    $context->pattern = '^\d+\.\d{2}$';
    $context->minLength = 1;
    $context->maxLength = 10;
    $context->minItems = 1;
    $context->maxItems = 5;
    $context->multipleOf = 5;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'default'          => 99.99,
        'format'           => 'double',
        'minimum'          => 0,
        'maximum'          => 1000,
        'exclusiveMinimum' => -1,
        'exclusiveMaximum' => 1001,
        'pattern'          => '^\d+\.\d{2}$',
        'minLength'        => 1,
        'maxLength'        => 10,
        'minItems'         => 1,
        'maxItems'         => 5,
        'multipleOf'       => 5,
    ]);
});

it('filters out null openApiAttributes when converting to Parameter', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('email', $property);
    $context->format = 'email';
    $context->minLength = 5;
    $context->minimum = null;
    $context->maximum = null;
    $context->pattern = null;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'format'    => 'email',
        'minLength' => 5,
    ]);
});

it('includes default value in both defaultValue and openApiAttributes', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('status', $property);
    $context->default = 'active';

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe(['default' => 'active'])
        ->and($parameter->openApiAttributes['default'])->toBe('active');
});

it('handles numeric zero as valid openApiAttribute value', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('count', $property);
    $context->default = 0;
    $context->minimum = 0;
    $context->maximum = 0;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'default' => 0,
        'minimum' => 0,
        'maximum' => 0,
    ]);
});

it('handles string constraints in openApiAttributes', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('username', $property);
    $context->pattern = '^[a-zA-Z0-9_]+$';
    $context->minLength = 3;
    $context->maxLength = 20;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'pattern'   => '^[a-zA-Z0-9_]+$',
        'minLength' => 3,
        'maxLength' => 20,
    ]);
});

it('handles array constraints in openApiAttributes', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('tags', $property);
    $context->minItems = 1;
    $context->maxItems = 10;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'minItems' => 1,
        'maxItems' => 10,
    ]);
});

it('handles numeric constraints in openApiAttributes', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('quantity', $property);
    $context->minimum = 1;
    $context->maximum = 100;
    $context->multipleOf = 5;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'minimum'    => 1,
        'maximum'    => 100,
        'multipleOf' => 5,
    ]);
});

it('handles exclusive numeric constraints in openApiAttributes', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('rating', $property);
    $context->exclusiveMinimum = 0;
    $context->exclusiveMaximum = 100;

    $parameter = $context->toParameter();

    expect($parameter->openApiAttributes)->toBe([
        'exclusiveMinimum' => 0,
        'exclusiveMaximum' => 100,
    ]);
});

it('uses default nullable value when nullable is null', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('field', $property);
    $context->nullable = null;

    $parameter = $context->toParameter();

    expect($parameter->nullable)->toBeTrue();
});

it('preserves nullable when explicitly set to false', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('field', $property);
    $context->nullable = false;

    $parameter = $context->toParameter();

    expect($parameter->nullable)->toBeFalse();
});

it('preserves nullable when explicitly set to true', function () {
    $property = mock(DataProperty::class);

    $context = new ParameterContext('field', $property);
    $context->nullable = true;

    $parameter = $context->toParameter();

    expect($parameter->nullable)->toBeTrue();
});
