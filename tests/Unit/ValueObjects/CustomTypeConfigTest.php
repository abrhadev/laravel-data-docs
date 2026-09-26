<?php

use Abrha\LaravelDataDocs\ValueObjects\CustomTypeConfig;

it('creates config with required fields only', function () {
    $config = new CustomTypeConfig(
        type: 'string',
        descriptions: ['Must be a valid string']
    );

    expect($config->type)->toBe('string')
        ->and($config->descriptions)->toBe(['Must be a valid string'])
        ->and($config->pattern)->toBeNull()
        ->and($config->format)->toBeNull()
        ->and($config->minimum)->toBeNull()
        ->and($config->maximum)->toBeNull();
});

it('creates config with all fields', function () {
    $config = new CustomTypeConfig(
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
    );

    expect($config->type)->toBe('string')
        ->and($config->descriptions)->toBe(['Complex type'])
        ->and($config->pattern)->toBe('^[A-Z]+$')
        ->and($config->format)->toBe('custom-format')
        ->and($config->minimum)->toBe(10)
        ->and($config->maximum)->toBe(100)
        ->and($config->exclusiveMinimum)->toBe(9)
        ->and($config->exclusiveMaximum)->toBe(101)
        ->and($config->minLength)->toBe(5)
        ->and($config->maxLength)->toBe(50)
        ->and($config->minItems)->toBe(1)
        ->and($config->maxItems)->toBe(10)
        ->and($config->multipleOf)->toBe(5);
});

it('names a missing required key', function (array $config, string $key) {
    expect(fn() => CustomTypeConfig::fromArray($config))
        ->toThrow(InvalidArgumentException::class, "Custom type config is missing the required key [{$key}].");
})->with([
    'type'         => [['descriptions' => ['A value.']], 'type'],
    'descriptions' => [['type' => 'string'], 'descriptions'],
]);

it('creates config from array with required fields', function () {
    $config = CustomTypeConfig::fromArray([
        'type'         => 'number',
        'descriptions' => ['Must be numeric'],
    ]);

    expect($config->type)->toBe('number')
        ->and($config->descriptions)->toBe(['Must be numeric'])
        ->and($config->pattern)->toBeNull();
});

it('creates config from array with all fields', function () {
    $config = CustomTypeConfig::fromArray([
        'type'             => 'string',
        'descriptions'     => ['Complex type'],
        'pattern'          => '^[A-Z]+$',
        'format'           => 'custom-format',
        'minimum'          => 10,
        'maximum'          => 100,
        'exclusiveMinimum' => 9,
        'exclusiveMaximum' => 101,
        'minLength'        => 5,
        'maxLength'        => 50,
        'minItems'         => 1,
        'maxItems'         => 10,
        'multipleOf'       => 5,
    ]);

    expect($config->type)->toBe('string')
        ->and($config->descriptions)->toBe(['Complex type'])
        ->and($config->pattern)->toBe('^[A-Z]+$')
        ->and($config->format)->toBe('custom-format')
        ->and($config->minimum)->toBe(10)
        ->and($config->maximum)->toBe(100)
        ->and($config->exclusiveMinimum)->toBe(9)
        ->and($config->exclusiveMaximum)->toBe(101)
        ->and($config->minLength)->toBe(5)
        ->and($config->maxLength)->toBe(50)
        ->and($config->minItems)->toBe(1)
        ->and($config->maxItems)->toBe(10)
        ->and($config->multipleOf)->toBe(5);
});

it('has readonly properties', function () {
    $config = new CustomTypeConfig(
        type: 'string',
        descriptions: ['Test']
    );

    expect($config)->toHaveProperty('type')
        ->and($config)->toHaveProperty('descriptions')
        ->and($config)->toHaveProperty('pattern');
});

it('accepts integral bounds given as floats or numeric strings', function () {
    $config = CustomTypeConfig::fromArray([
        'type'         => 'number',
        'descriptions' => [],
        'minimum'      => 5.0,
        'maximum'      => '10',
        'multipleOf'   => 2.0,
    ]);

    expect($config->minimum)->toBe(5)
        ->and($config->maximum)->toBe(10)
        ->and($config->multipleOf)->toBe(2);
});

it('rejects a bound an int cannot hold exactly', function (string $key, mixed $value) {
    CustomTypeConfig::fromArray(['type' => 'number', 'descriptions' => [], $key => $value]);
})->with([
    'fractional minimum'    => ['minimum', 2.5],
    'fractional string'     => ['maximum', '2.5'],
    'fractional multipleOf' => ['multipleOf', 0.01],
    'overflowing bound'     => ['maxLength', 1e20],
    'non-finite bound'      => ['exclusiveMaximum', INF],
    'non-numeric bound'     => ['minItems', 'many'],
])->throws(InvalidArgumentException::class, 'must be an integer');

it('rejects a multipleOf that is not positive', function (int $value) {
    CustomTypeConfig::fromArray(['type' => 'number', 'descriptions' => [], 'multipleOf' => $value]);
})->with([0, -3])->throws(InvalidArgumentException::class, 'must be greater than zero');

it('rejects a negative length or item count', function (string $key) {
    CustomTypeConfig::fromArray(['type' => 'string', 'descriptions' => [], $key => -1]);
})->with(['minLength', 'maxLength', 'minItems', 'maxItems'])->throws(InvalidArgumentException::class, 'must not be negative');

it('accepts zero lengths and item counts and negative numeric bounds', function () {
    $config = CustomTypeConfig::fromArray([
        'type'             => 'number',
        'descriptions'     => [],
        'minLength'        => 0,
        'minItems'         => 0,
        'minimum'          => -10,
        'exclusiveMaximum' => -1,
    ]);

    expect($config->minLength)->toBe(0)
        ->and($config->minItems)->toBe(0)
        ->and($config->minimum)->toBe(-10)
        ->and($config->exclusiveMaximum)->toBe(-1);
});
