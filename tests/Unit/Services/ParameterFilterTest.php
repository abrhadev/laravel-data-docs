<?php

use Abrha\LaravelDataDocs\Services\ParameterFilter;
use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;

beforeEach(function () {
    $this->filter = new ParameterFilter();
});

it('returns all parameters for GET request without QueryParameter attributes when target is QUERY', function () {
    $parameters = [
        new Parameter('name', 'string', true, false, ParameterLocation::BODY),
        new Parameter('email', 'string', true, false, ParameterLocation::BODY),
        new Parameter('age', 'integer', false, false, ParameterLocation::BODY),
    ];

    $result = $this->filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::QUERY);

    expect($result)->toHaveCount(3)
        ->and(array_keys($result))->toBe([0, 1, 2]);
});

it('returns empty array for GET request without QueryParameter attributes when target is BODY', function () {
    $parameters = [
        new Parameter('name', 'string', true, false, ParameterLocation::BODY),
        new Parameter('email', 'string', true, false, ParameterLocation::BODY),
    ];

    $result = $this->filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::BODY);

    expect($result)->toBeEmpty();
});

it('filters by location for GET request with some QueryParameter attributes', function () {
    $parameters = [
        new Parameter('search', 'string', false, false, ParameterLocation::QUERY),
        new Parameter('name', 'string', true, false, ParameterLocation::BODY),
        new Parameter('email', 'string', true, false, ParameterLocation::BODY),
    ];

    $queryResult = $this->filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::QUERY);
    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(1)
        ->and($queryResult[0]->name)->toBe('search')
        ->and($bodyResult)->toHaveCount(2)
        ->and($bodyResult[1]->name)->toBe('name')
        ->and($bodyResult[2]->name)->toBe('email');
});

it('filters by location for POST request', function () {
    $parameters = [
        new Parameter('search', 'string', false, false, ParameterLocation::QUERY),
        new Parameter('name', 'string', true, false, ParameterLocation::BODY),
        new Parameter('email', 'string', true, false, ParameterLocation::BODY),
    ];

    $queryResult = $this->filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::QUERY);
    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(1)
        ->and($queryResult[0]->name)->toBe('search')
        ->and($bodyResult)->toHaveCount(2)
        ->and($bodyResult[1]->name)->toBe('name')
        ->and($bodyResult[2]->name)->toBe('email');
});

it('filters by location for PUT request', function () {
    $parameters = [
        new Parameter('filter', 'string', false, false, ParameterLocation::QUERY),
        new Parameter('title', 'string', true, false, ParameterLocation::BODY),
    ];

    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['PUT'], ParameterLocation::BODY);

    expect($bodyResult)->toHaveCount(1)
        ->and($bodyResult[1]->name)->toBe('title');
});

it('filters by location for DELETE request', function () {
    $parameters = [
        new Parameter('confirm', 'boolean', false, false, ParameterLocation::QUERY),
        new Parameter('reason', 'string', false, false, ParameterLocation::BODY),
    ];

    $queryResult = $this->filter->filterByHttpMethod($parameters, ['DELETE'], ParameterLocation::QUERY);

    expect($queryResult)->toHaveCount(1)
        ->and($queryResult[0]->name)->toBe('confirm');
});

it('handles mixed GET and POST methods by using POST filtering rules', function () {
    $parameters = [
        new Parameter('search', 'string', false, false, ParameterLocation::QUERY),
        new Parameter('name', 'string', true, false, ParameterLocation::BODY),
    ];

    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['GET', 'POST'], ParameterLocation::BODY);

    expect($bodyResult)->toHaveCount(1)
        ->and($bodyResult[1]->name)->toBe('name');
});

it('handles empty parameters array', function () {
    $result = $this->filter->filterByHttpMethod([], ['GET'], ParameterLocation::QUERY);

    expect($result)->toBeEmpty();
});

it('handles all QUERY parameters for GET request', function () {
    $parameters = [
        new Parameter('search', 'string', false, false, ParameterLocation::QUERY),
        new Parameter('filter', 'string', false, false, ParameterLocation::QUERY),
        new Parameter('sort', 'string', false, false, ParameterLocation::QUERY),
    ];

    $queryResult = $this->filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::QUERY);
    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(3)
        ->and($bodyResult)->toBeEmpty();
});

it('handles all BODY parameters for POST request', function () {
    $parameters = [
        new Parameter('name', 'string', true, false, ParameterLocation::BODY),
        new Parameter('email', 'string', true, false, ParameterLocation::BODY),
        new Parameter('password', 'string', true, false, ParameterLocation::BODY),
    ];

    $queryResult = $this->filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::QUERY);
    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::BODY);

    expect($queryResult)->toBeEmpty()
        ->and($bodyResult)->toHaveCount(3);
});

it('preserves parameter keys in filtered results', function () {
    $parameters = [
        'user.name'  => new Parameter('user.name', 'string', true, false, ParameterLocation::BODY),
        'user.email' => new Parameter('user.email', 'string', true, false, ParameterLocation::BODY),
        'search'     => new Parameter('search', 'string', false, false, ParameterLocation::QUERY),
    ];

    $bodyResult = $this->filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::BODY);

    expect($bodyResult)->toHaveKeys(['user.name', 'user.email'])
        ->and($bodyResult)->not->toHaveKey('search');
});
