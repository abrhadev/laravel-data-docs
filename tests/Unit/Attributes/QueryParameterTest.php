<?php

use Abrha\LaravelDataDocs\Attributes\QueryParameter;

it('can be instantiated', function () {
    $queryParam = new QueryParameter();

    expect($queryParam)->toBeInstanceOf(QueryParameter::class);
});

it('can be used as attribute on properties', function () {
    $reflection = new ReflectionClass(TestClassWithQueryParameter::class);
    $property = $reflection->getProperty('searchTerm');
    $attributes = $property->getAttributes(QueryParameter::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance())->toBeInstanceOf(QueryParameter::class);
});

it('does not appear on properties without attribute', function () {
    $reflection = new ReflectionClass(TestClassWithQueryParameter::class);
    $property = $reflection->getProperty('bodyField');
    $attributes = $property->getAttributes(QueryParameter::class);

    expect($attributes)->toBeEmpty();
});

class TestClassWithQueryParameter
{
    #[QueryParameter]
    public string $searchTerm;

    public string $bodyField;
}
