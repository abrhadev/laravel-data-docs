<?php

use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;

it('has QUERY case with correct value', function () {
    expect(ParameterLocation::QUERY->value)->toBe('query');
});

it('has BODY case with correct value', function () {
    expect(ParameterLocation::BODY->value)->toBe('body');
});

it('can be compared using identity', function () {
    $location1 = ParameterLocation::QUERY;
    $location2 = ParameterLocation::QUERY;
    $location3 = ParameterLocation::BODY;

    expect($location1 === $location2)->toBeTrue()
        ->and($location1 === $location3)->toBeFalse();
});

it('can be instantiated from string value', function () {
    $query = ParameterLocation::from('query');
    $body = ParameterLocation::from('body');

    expect($query)->toBe(ParameterLocation::QUERY)
        ->and($body)->toBe(ParameterLocation::BODY);
});

it('throws error for invalid value', function () {
    ParameterLocation::from('invalid');
})->throws(ValueError::class);

it('can get all cases', function () {
    $cases = ParameterLocation::cases();

    expect($cases)->toHaveCount(2)
        ->and($cases[0])->toBe(ParameterLocation::QUERY)
        ->and($cases[1])->toBe(ParameterLocation::BODY);
});

it('tryFrom returns null for invalid value', function () {
    $result = ParameterLocation::tryFrom('invalid');

    expect($result)->toBeNull();
});

it('tryFrom returns correct case for valid value', function () {
    $query = ParameterLocation::tryFrom('query');
    $body = ParameterLocation::tryFrom('body');

    expect($query)->toBe(ParameterLocation::QUERY)
        ->and($body)->toBe(ParameterLocation::BODY);
});
