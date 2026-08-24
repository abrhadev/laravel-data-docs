<?php

use Abrha\LaravelDataDocs\Attributes\Description;

it('can be instantiated with a single description', function () {
    $description = new Description('The unique identifier of the user');

    expect($description)->toBeInstanceOf(Description::class)
        ->and($description->descriptions)->toBe(['The unique identifier of the user']);
});

it('can be instantiated with multiple descriptions', function () {
    $description = new Description('First sentence.', 'Second sentence.');

    expect($description->descriptions)->toBe(['First sentence.', 'Second sentence.']);
});

it('can be instantiated with no descriptions', function () {
    $description = new Description();

    expect($description->descriptions)->toBe([]);
});

it('can be used as attribute on properties', function () {
    $reflection = new ReflectionClass(TestClassWithDescription::class);
    $property = $reflection->getProperty('field');
    $attributes = $property->getAttributes(Description::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance())->toBeInstanceOf(Description::class)
        ->and($attributes[0]->newInstance()->descriptions)->toBe(['example value']);
});

it('can be repeated on the same property', function () {
    $reflection = new ReflectionClass(TestClassWithDescription::class);
    $property = $reflection->getProperty('repeated');
    $attributes = $property->getAttributes(Description::class);

    expect($attributes)->toHaveCount(2)
        ->and($attributes[0]->newInstance()->descriptions)->toBe(['Line A'])
        ->and($attributes[1]->newInstance()->descriptions)->toBe(['Line B']);
});

it('does not appear on properties without attribute', function () {
    $reflection = new ReflectionClass(TestClassWithDescription::class);
    $property = $reflection->getProperty('fieldWithoutDescription');
    $attributes = $property->getAttributes(Description::class);

    expect($attributes)->toBeEmpty();
});

class TestClassWithDescription
{
    #[Description('example value')]
    public string $field;

    #[Description('Line A')]
    #[Description('Line B')]
    public string $repeated;

    public string $fieldWithoutDescription;
}
