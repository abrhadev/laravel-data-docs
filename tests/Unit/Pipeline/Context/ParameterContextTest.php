<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\ConfirmationCompanion;
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

it('defaults the never-satisfiable flag to false and keeps it out of the Parameter', function () {
    $context = new ParameterContext('locked', mock(DataProperty::class));

    expect($context->neverSatisfiable)->toBeFalse();

    $context->neverSatisfiable = true;

    expect($context->toParameter()->toArray())->not->toHaveKey('neverSatisfiable')
        ->and($context->toParameter()->openApiAttributes)->toBe([]);
});

it('carries a confirmation companion without publishing it', function () {
    $context = new ParameterContext('password', mock(DataProperty::class));

    expect($context->confirmationCompanion)->toBeNull();

    $context->confirmationCompanion = new ConfirmationCompanion('password_confirmation', 'Must match.', 'Required when sent.');

    expect($context->toParameter()->toArray())->not->toHaveKey('confirmationCompanion')
        ->and($context->toParameter()->openApiAttributes)->toBe([]);
});

it('carries a date format for the example without publishing it', function () {
    $context = new ParameterContext('born_on', mock(DataProperty::class));

    expect($context->dateFormat)->toBeNull();

    $context->dateFormat = 'd/m/Y';

    expect($context->toParameter()->toArray())->not->toHaveKey('dateFormat')
        ->and($context->toParameter()->openApiAttributes)->toBe([]);
});

it('keeps numeric bounds of a numeric string out of the published schema', function () {
    $context = new ParameterContext('code', mock(DataProperty::class));

    expect($context->numericString)->toBeFalse();

    $context->type = 'string';
    $context->numericString = true;
    $context->minimum = 1;
    $context->maximum = 9;
    $context->exclusiveMinimum = 0;
    $context->exclusiveMaximum = 10;
    $context->maxLength = 4;

    expect($context->toParameter()->openApiAttributes)->toBe(['maxLength' => 4])
        ->and($context->toParameter()->toArray())->not->toHaveKey('numericString');
});

it('publishes a fractional bound exactly and an integral one as an int', function () {
    $context = new ParameterContext('ratio', mock(DataProperty::class));
    $context->type = 'number';
    $context->maximum = 3;
    $context->exclusiveMinimum = 0.5;

    expect($context->toParameter()->openApiAttributes)->toBe(['maximum' => 3, 'exclusiveMinimum' => 0.5]);
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

it('types the allowed values to the field', function (string $type, array $allowed, array $expected) {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = $type;
    $context->allowedValues = $allowed;

    expect($context->allowedValueList())->toBe($expected);
})->with([
    'string'        => ['string', ['a', 'b'], ['a', 'b']],
    'integer'       => ['integer', ['1', '-2'], [1, -2]],
    'number'        => ['number', ['1.5', '2'], [1.5, 2.0]],
    'boolean'       => ['boolean', ['1', '0'], [true]],
    'boolean false' => ['boolean', ['1', ''], [true, false]],
    'integer items' => ['integer[]', ['3'], [3]],
]);

it('publishes no allowed-value list when the set is unset or empty', function (?array $allowed) {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = 'string';
    $context->allowedValues = $allowed;

    expect($context->allowedValueList())->toBeNull()
        ->and($context->toParameter()->enumValues)->toBeNull();
})->with([
    'unset' => [null],
    'empty' => [[]],
]);

it('narrows the allowed values to those the value pattern accepts', function (?array $allowed, ?string $valuePattern, ?array $expected) {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->allowedValues = $allowed;
    $context->valuePatterns = $valuePattern === null ? [] : [$valuePattern];

    expect($context->acceptedAllowedValues())->toBe($expected);
})->with([
    'a pattern filters the values'         => [['eur', 'USD'], '^[^\p{Ll}\p{Lt}]*$', ['USD']],
    'no pattern keeps every value'         => [['eur', 'USD'], null, ['eur', 'USD']],
    'an uncompilable pattern filters none' => [['eur', 'USD'], '(', ['eur', 'USD']],
    'no allowed values stays null'         => [null, '^[^\p{Ll}\p{Lt}]*$', null],
]);

it('publishes the allowed values as enumValues and never the excluded values', function () {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = 'integer';
    $context->allowedValues = ['1', '2'];
    $context->excludedValues = ['3'];

    $parameter = $context->toParameter();

    expect($parameter->enumValues)->toBe([1, 2])
        ->and($parameter->openApiAttributes)->not->toHaveKey('excludedValues')
        ->and(json_encode($parameter->toArray()))->not->toContain('3');
});

it('keeps the exact pattern of an enum even when one of its cases fails it', function () {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = 'string';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ContextTestStringBackedEnum::cases());
    $context->pattern = '^[A-Z]+$';
    $context->valuePatterns = ['^[A-Z]+$'];

    $parameter = $context->toParameter();

    expect($parameter->enumValues)->toBe(['a', 'b'])
        ->and($parameter->openApiAttributes['pattern'] ?? null)->toBe('^[A-Z]+$');
});

it('drops the pattern of an allowed-value set one of whose values fails it', function () {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = 'string';
    $context->allowedValues = ['ABC', 'abc'];
    $context->pattern = '^[A-Z]+$';

    $parameter = $context->toParameter();

    expect($parameter->enumValues)->toBe(['ABC', 'abc'])
        ->and($parameter->openApiAttributes)->not->toHaveKey('pattern');
});

it('never publishes the example-only hints', function () {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = 'string';
    $context->uriScheme = 'ftp';
    $context->exampleFormat = 'ipv4';

    $published = json_encode($context->toParameter()->toArray());

    expect($published)->not->toContain('ftp')
        ->not->toContain('ipv4')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('format');
});

it('runs the value rules on a boolean as true or false, not its string form', function (string $rule, array $expected) {
    $context = new ParameterContext('flag', mock(DataProperty::class));
    $context->type = 'boolean';
    $context->allowedValues = ['1', '', '0'];
    $context->valueRules = [$rule];

    expect($context->acceptedAllowedValues())->toBe($expected);
})->with([
    'declined' => ['declined', ['', '0']],
    'accepted' => ['accepted', ['1']],
]);

it('keeps the string form for the value rules of a field that is not a boolean', function () {
    $context = new ParameterContext('text', mock(DataProperty::class));
    $context->type = 'string';
    $context->allowedValues = ['1', '', 'no'];
    $context->valueRules = ['declined'];

    expect($context->acceptedAllowedValues())->toBe(['no']);
});

it('keeps the enum cases a predicate accepts, and none leaves an empty allowed set', function () {
    $context = new ParameterContext('size', mock(DataProperty::class));
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ContextTestStringBackedEnum::cases());

    $context->keepEnumCases(fn(ContextTestStringBackedEnum $case) => $case->value === 'b');

    expect($context->enumInfo?->toArray())->toBe(['b']);

    $context->keepEnumCases(fn() => false);

    expect($context->enumInfo)->toBeNull()
        ->and($context->allowedValues)->toBe([]);
});

it('weighs an example for an approximate pattern only when its bounds and the In set accept it', function (?array $allowed, ?int $maxLength, string $example, bool $kept) {
    $context = new ParameterContext('code', mock(DataProperty::class));
    $context->type = 'string';
    $context->pattern = '^[a-z]+$';
    $context->valuePatterns = ['^\p{Ll}+$'];
    $context->allowedValues = $allowed;
    $context->maxLength = $maxLength;
    $context->example = $example;

    expect(array_key_exists('pattern', $context->toParameter()->openApiAttributes))->toBe($kept);
})->with([
    'accepted by every rule'  => [null, null, 'café', false],
    'longer than maxLength'   => [null, 3, 'café', true],
    'outside the In set'      => [['abc'], null, 'café', true],
    'rejected by the pattern' => [null, null, 'CAFÉ', true],
]);
