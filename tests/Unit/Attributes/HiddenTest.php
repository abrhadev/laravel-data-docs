<?php

use Abrha\LaravelDataDocs\Attributes\Hidden;

it('can be instantiated', function () {
    $hidden = new Hidden();

    expect($hidden)->toBeInstanceOf(Hidden::class);
});

it('can be used as attribute on properties', function () {
    $reflection = new ReflectionClass(TestClassWithHidden::class);
    $property = $reflection->getProperty('hiddenField');
    $attributes = $property->getAttributes(Hidden::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance())->toBeInstanceOf(Hidden::class);
});

it('does not appear on properties without attribute', function () {
    $reflection = new ReflectionClass(TestClassWithHidden::class);
    $property = $reflection->getProperty('visibleField');
    $attributes = $property->getAttributes(Hidden::class);

    expect($attributes)->toBeEmpty();
});

class TestClassWithHidden
{
    #[Hidden]
    public string $hiddenField;

    public string $visibleField;
}
