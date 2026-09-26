<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\TypeDescriptionStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Support\DataProperty;

beforeEach(function () {
    $this->stage = new TypeDescriptionStage();
});

it('sets description for boolean type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'boolean';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a boolean.');
});

it('sets description for integer type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'integer';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an integer.');
});

it('sets description for number type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'number';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a number.');
});

it('sets description for string type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.');
});

it('sets description for object type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'object';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an object.');
});

it('sets description for object array type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'object[]';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of objects.');
});

it('sets description for string array type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string[]';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of strings.');
});

it('sets description for integer array type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'integer[]';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of integers.');
});

it('sets description for boolean array type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'boolean[]';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of booleans.');
});

it('sets description for number array type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'number[]';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of numbers.');
});

it('does not set description for unknown type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'unknown';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('');
});

it('does not set description when type is null', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = null;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('');
});

it('returns the same context instance', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

it('sets description for backed enum with different name and value', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [TestBackedEnum::ACTIVE, TestBackedEnum::INACTIVE]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>ACTIVE</code> (active), <code>INACTIVE</code> (inactive).');
});

it('sets description for backed enum with same name and value', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [TestSameValueEnum::active, TestSameValueEnum::inactive]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>active</code> (active), <code>inactive</code> (inactive).');
});

it('sets description for pure enum', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::PURE,
        cases: [TestUnitEnum::RED, TestUnitEnum::BLUE]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>RED</code>, <code>BLUE</code>.');
});

it('sets description for array of enums', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string[]';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::PURE,
        cases: [TestUnitEnum::RED, TestUnitEnum::BLUE]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of enums. Each item must be one of: <code>RED</code>, <code>BLUE</code>.');
});

it('prioritizes enum description over type description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::PURE,
        cases: [TestUnitEnum::RED]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>RED</code>.');
});

it('prepends type description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';
    $context->description = 'This is the user email.';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string. This is the user email.');
});

it('prepends enum description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';
    $context->description = 'The status of the user account.';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [TestBackedEnum::ACTIVE, TestBackedEnum::INACTIVE]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>ACTIVE</code> (active), <code>INACTIVE</code> (inactive). The status of the user account.');
});

it('prepends array type description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string[]';
    $context->description = 'Tags for the product.';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of strings. Tags for the product.');
});

it('prepends array enum description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string[]';
    $context->description = 'Available colors for this product.';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::PURE,
        cases: [TestUnitEnum::RED, TestUnitEnum::BLUE]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an array of enums. Each item must be one of: <code>RED</code>, <code>BLUE</code>. Available colors for this product.');
});

it('handles empty existing description with type', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'integer';
    $context->description = '';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an integer.');
});

it('handles empty existing description with enum', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';
    $context->description = '';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::PURE,
        cases: [TestUnitEnum::RED]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>RED</code>.');
});

it('prepends boolean type description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'boolean';
    $context->description = 'Whether the user is active.';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a boolean. Whether the user is active.');
});

it('prepends integer type description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'integer';
    $context->description = 'The age of the user.';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be an integer. The age of the user.');
});

it('prepends int backed enum description to existing description', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'integer';
    $context->description = 'Priority level for the task.';

    $context->enumInfo = new EnumInfo(
        enumType: EnumType::INT_BACKED,
        cases: [TestIntBackedEnum::HIGH, TestIntBackedEnum::LOW]
    );

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be one of: <code>HIGH</code> (1), <code>LOW</code> (0). Priority level for the task.');
});

it('does not modify description for unknown type when existing description is present', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'custom_type';
    $context->description = 'Custom field description.';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Custom field description.');
});

enum TestBackedEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}

enum TestSameValueEnum: string
{
    case active = 'active';
    case inactive = 'inactive';
}

enum TestUnitEnum
{
    case RED;
    case BLUE;
}

it('writes the allowed and excluded value sentences after the lead', function (string $type, ?array $allowed, ?array $excluded, string $description) {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = $type;
    $context->allowedValues = $allowed;
    $context->excludedValues = $excluded;
    $context->description = 'Existing.';

    expect($this->stage->process($context)->description)->toBe($description);
})->with([
    'allowed'        => ['string', ['a', 'b'], null, 'Must be a string. Must be one of: <code>a</code>, <code>b</code>. Existing.'],
    'allowed items'  => ['string[]', ['a'], null, 'Must be an array of strings. Each item must be one of: <code>a</code>. Existing.'],
    'empty allowed'  => ['string', [], null, 'Must be a string. Note: the list of allowed values is empty, so any request that sends this field fails validation. Existing.'],
    'excluded'       => ['string', null, ['x'], 'Must be a string. Must not be one of: <code>x</code>. Existing.'],
    'excluded items' => ['string[]', null, ['x'], 'Must be an array of strings. Must include at least one item that is not one of: <code>x</code>. Existing.'],
    'reconciled'     => ['string', ['a'], ['x'], 'Must be a string. Must be one of: <code>a</code>. Existing.'],
    'unknown type'   => ['mixed', ['a'], null, 'Must be one of: <code>a</code>. Existing.'],
]);

it('lists only the allowed values the value pattern accepts', function (string $type, array $allowed, ?string $valuePattern, string $description) {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = $type;
    $context->allowedValues = $allowed;
    $context->valuePatterns = $valuePattern === null ? [] : [$valuePattern];

    expect($this->stage->process($context)->description)->toBe($description);
})->with([
    'pattern filters'       => ['string', ['eur', 'USD'], '^[^\p{Ll}\p{Lt}]*$', 'Must be a string. Must be one of: <code>USD</code>.'],
    'pattern filters items' => ['string[]', ['eur', 'USD'], '^[^\p{Ll}\p{Lt}]*$', 'Must be an array of strings. Each item must be one of: <code>USD</code>.'],
    'pattern accepts none'  => ['string', ['eur'], '^[^\p{Ll}\p{Lt}]*$', 'Must be a string. Note: the list of allowed values is empty, so any request that sends this field fails validation.'],
    'uncompilable pattern'  => ['string', ['eur', 'USD'], '(', 'Must be a string. Must be one of: <code>eur</code>, <code>USD</code>.'],
]);

it('lists boolean allowed values as Laravel compares them', function (array $allowed, string $description) {
    $context = new ParameterContext('test', mock(DataProperty::class));
    $context->type = 'boolean';
    $context->allowedValues = $allowed;

    expect($this->stage->process($context)->description)->toBe($description);
})->with([
    'true and false' => [['1', ''], 'Must be a boolean. Must be one of: <code>true</code>, <code>false</code>.'],
    'string zero'    => [['0'], 'Must be a boolean. Must be one of: <code>"0"</code>.'],
]);

enum TestIntBackedEnum: int
{
    case HIGH = 1;
    case LOW = 0;
}
