<?php

use Abrha\LaravelDataDocs\ValueObjects\ExtractionStrategy;

it('has BODY_PARAMETERS case with value 1', function () {
    expect(ExtractionStrategy::BODY_PARAMETERS->value)->toBe(1);
});

it('has QUERY_PARAMETERS case with value 2', function () {
    expect(ExtractionStrategy::QUERY_PARAMETERS->value)->toBe(2);
});

it('can be compared using identity', function () {
    $strategy1 = ExtractionStrategy::BODY_PARAMETERS;
    $strategy2 = ExtractionStrategy::BODY_PARAMETERS;
    $strategy3 = ExtractionStrategy::QUERY_PARAMETERS;

    expect($strategy1 === $strategy2)->toBeTrue()
        ->and($strategy1 === $strategy3)->toBeFalse();
});

it('can be instantiated from int value', function () {
    $body = ExtractionStrategy::from(1);
    $query = ExtractionStrategy::from(2);

    expect($body)->toBe(ExtractionStrategy::BODY_PARAMETERS)
        ->and($query)->toBe(ExtractionStrategy::QUERY_PARAMETERS);
});

it('throws error for invalid int value', function () {
    ExtractionStrategy::from(99);
})->throws(ValueError::class);

it('can get all cases', function () {
    $cases = ExtractionStrategy::cases();

    expect($cases)->toHaveCount(2)
        ->and($cases[0])->toBe(ExtractionStrategy::BODY_PARAMETERS)
        ->and($cases[1])->toBe(ExtractionStrategy::QUERY_PARAMETERS);
});

it('tryFrom returns null for invalid int value', function () {
    $result = ExtractionStrategy::tryFrom(99);

    expect($result)->toBeNull();
});

it('tryFrom returns correct case for valid int value', function () {
    $body = ExtractionStrategy::tryFrom(1);
    $query = ExtractionStrategy::tryFrom(2);

    expect($body)->toBe(ExtractionStrategy::BODY_PARAMETERS)
        ->and($query)->toBe(ExtractionStrategy::QUERY_PARAMETERS);
});
