<?php

use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;

it('creates parameter with all properties', function () {
    $parameter = new Parameter(
        name: 'test',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY,
        description: 'Test parameter',
        example: 'example value',
        enumValues: ['a', 'b', 'c'],
        openApiAttributes: ['minimum' => 1, 'maximum' => 10, 'default' => 'default']
    );

    expect($parameter->name)->toBe('test')
        ->and($parameter->type)->toBe('string')
        ->and($parameter->required)->toBeTrue()
        ->and($parameter->location)->toBe(ParameterLocation::BODY)
        ->and($parameter->description)->toBe('Test parameter')
        ->and($parameter->example)->toBe('example value')
        ->and($parameter->enumValues)->toBe(['a', 'b', 'c'])
        ->and($parameter->openApiAttributes)->toBe(['minimum' => 1, 'maximum' => 10, 'default' => 'default'])
        ->and($parameter->openApiAttributes['default'])->toBe('default');
});

it('converts parameter to array with all fields', function () {
    $parameter = new Parameter(
        name: 'age',
        type: 'int',
        required: true,
        nullable: false,
        location: ParameterLocation::QUERY,
        description: 'User age',
        example: 25
    );

    $array = $parameter->toArray();

    expect($array)->toHaveKeys(['name', 'required', 'type', 'nullable', 'description', 'example'])
        ->and($array['name'])->toBe('age')
        ->and($array['required'])->toBeTrue()
        ->and($array['type'])->toBe('int')
        ->and($array['nullable'])->toBeFalse()
        ->and($array['description'])->toBe('User age')
        ->and($array['example'])->toBe(25);
});

it('includes enum values in toArray when present', function () {
    $parameter = new Parameter(
        name: 'status',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY,
        enumValues: ['active', 'inactive', 'pending']
    );

    $array = $parameter->toArray();

    expect($array)->toHaveKey('enumValues')
        ->and($array['enumValues'])->toBe(['active', 'inactive', 'pending']);
});

it('excludes enum values from toArray when not set', function () {
    $parameter = new Parameter(
        name: 'name',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY
    );

    $array = $parameter->toArray();

    expect($array)->not->toHaveKey('enumValues');
});

it('includes custom openAPI attributes in toArray when present', function () {
    $parameter = new Parameter(
        name: 'age',
        type: 'int',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY,
        openApiAttributes: ['minimum' => 18, 'maximum' => 100]
    );

    $array = $parameter->toArray();

    expect($array)->toHaveKey('custom')
        ->and($array['custom'])->toHaveKey('openAPI')
        ->and($array['custom']['openAPI'])->toBe(['minimum' => 18, 'maximum' => 100]);
});

it('excludes custom openAPI from toArray when attributes are empty', function () {
    $parameter = new Parameter(
        name: 'name',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY,
        openApiAttributes: []
    );

    $array = $parameter->toArray();

    expect($array)->not->toHaveKey('custom');
});

it('creates new parameter with different location using withLocation', function () {
    $bodyParameter = new Parameter(
        name: 'test',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY,
        description: 'Original'
    );

    $queryParameter = $bodyParameter->withLocation(ParameterLocation::QUERY);

    expect($queryParameter)->not->toBe($bodyParameter)
        ->and($queryParameter->name)->toBe('test')
        ->and($queryParameter->type)->toBe('string')
        ->and($queryParameter->required)->toBeTrue()
        ->and($queryParameter->location)->toBe(ParameterLocation::QUERY)
        ->and($queryParameter->description)->toBe('Original');
});

it('checks if parameter matches location', function () {
    $bodyParameter = new Parameter(
        name: 'test',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY
    );

    expect($bodyParameter->matchesLocation(ParameterLocation::BODY))->toBeTrue()
        ->and($bodyParameter->matchesLocation(ParameterLocation::QUERY))->toBeFalse();
});

it('sets nullable to opposite of required in toArray', function () {
    $requiredParameter = new Parameter(
        name: 'required',
        type: 'string',
        required: true,
        nullable: false,
        location: ParameterLocation::BODY
    );

    $optionalParameter = new Parameter(
        name: 'optional',
        type: 'string',
        required: false,
        nullable: true,
        location: ParameterLocation::BODY
    );

    expect($requiredParameter->toArray()['nullable'])->toBeFalse()
        ->and($optionalParameter->toArray()['nullable'])->toBeTrue();
});
