<?php

use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterFilter;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

it('filters parameters correctly for GET request without QueryParameter attributes', function () {
    $dataClass = new class extends Data {
        public string $name;

        public string $email;

        public int $age;
    };

    $generator = new ParameterGenerator(
        PipelineFactory::createDefault(),
        app(DataConfig::class)
    );

    $parameters = $generator($dataClass::class);
    $filter = new ParameterFilter();

    $queryResult = $filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::QUERY);
    $bodyResult = $filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(3)
        ->and($bodyResult)->toBeEmpty();
});

it('filters parameters correctly for GET request with QueryParameter attributes', function () {
    $dataClass = new class extends Data {
        #[QueryParameter]
        public string $search;

        public string $name;

        public string $email;
    };

    $generator = new ParameterGenerator(
        PipelineFactory::createDefault(),
        app(DataConfig::class)
    );

    $parameters = $generator($dataClass::class);
    $filter = new ParameterFilter();

    $queryResult = $filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::QUERY);
    $bodyResult = $filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(1)
        ->and(array_values($queryResult)[0]->name)->toBe('search')
        ->and($bodyResult)->toHaveCount(2)
        ->and(array_values($bodyResult)[0]->name)->toBe('name')
        ->and(array_values($bodyResult)[1]->name)->toBe('email');
});

it('filters parameters correctly for POST request with QueryParameter attributes', function () {
    $dataClass = new class extends Data {
        #[QueryParameter]
        public string $filter;

        public string $name;

        public string $email;
    };

    $generator = new ParameterGenerator(
        PipelineFactory::createDefault(),
        app(DataConfig::class)
    );

    $parameters = $generator($dataClass::class);
    $filter = new ParameterFilter();

    $queryResult = $filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::QUERY);
    $bodyResult = $filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(1)
        ->and(array_values($queryResult)[0]->name)->toBe('filter')
        ->and($bodyResult)->toHaveCount(2)
        ->and(array_values($bodyResult)[0]->name)->toBe('name')
        ->and(array_values($bodyResult)[1]->name)->toBe('email');
});

it('filters parameters correctly for POST request without QueryParameter attributes', function () {
    $dataClass = new class extends Data {
        public string $name;

        public string $email;

        public string $password;
    };

    $generator = new ParameterGenerator(
        PipelineFactory::createDefault(),
        app(DataConfig::class)
    );

    $parameters = $generator($dataClass::class);
    $filter = new ParameterFilter();

    $queryResult = $filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::QUERY);
    $bodyResult = $filter->filterByHttpMethod($parameters, ['POST'], ParameterLocation::BODY);

    expect($queryResult)->toBeEmpty()
        ->and($bodyResult)->toHaveCount(3);
});

it('handles multiple query parameters', function () {
    $dataClass = new class extends Data {
        #[QueryParameter]
        public string $sort;

        #[QueryParameter]
        public string $filter;

        public string $name;

        public string $email;
    };

    $generator = new ParameterGenerator(
        PipelineFactory::createDefault(),
        app(DataConfig::class)
    );

    $parameters = $generator($dataClass::class);
    $filter = new ParameterFilter();

    $queryResult = $filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::QUERY);
    $bodyResult = $filter->filterByHttpMethod($parameters, ['GET'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(2)
        ->and(array_values($queryResult)[0]->name)->toBe('sort')
        ->and(array_values($queryResult)[1]->name)->toBe('filter')
        ->and($bodyResult)->toHaveCount(2)
        ->and(array_values($bodyResult)[0]->name)->toBe('name')
        ->and(array_values($bodyResult)[1]->name)->toBe('email');
});

it('filters parameters correctly for PUT request', function () {
    $dataClass = new class extends Data {
        #[QueryParameter]
        public bool $notify;

        public string $title;

        public string $content;
    };

    $generator = new ParameterGenerator(
        PipelineFactory::createDefault(),
        app(DataConfig::class)
    );

    $parameters = $generator($dataClass::class);
    $filter = new ParameterFilter();

    $queryResult = $filter->filterByHttpMethod($parameters, ['PUT'], ParameterLocation::QUERY);
    $bodyResult = $filter->filterByHttpMethod($parameters, ['PUT'], ParameterLocation::BODY);

    expect($queryResult)->toHaveCount(1)
        ->and(array_values($queryResult)[0]->name)->toBe('notify')
        ->and($bodyResult)->toHaveCount(2);
});
