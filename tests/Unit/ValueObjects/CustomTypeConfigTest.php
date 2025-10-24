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
