<?php

use Abrha\LaravelDataDocs\Attributes\Example;

it('can be instantiated with a value', function () {
    $example = new Example('test value');

    expect($example)->toBeInstanceOf(Example::class)
        ->and($example->value)->toBe('test value');
});

it('can store different value types', function () {
    $stringExample = new Example('string');
    $intExample = new Example(42);
    $arrayExample = new Example(['a', 'b', 'c']);
    $boolExample = new Example(true);

    expect($stringExample->value)->toBe('string')
        ->and($intExample->value)->toBe(42)
        ->and($arrayExample->value)->toBe(['a', 'b', 'c'])
        ->and($boolExample->value)->toBeTrue();
});

it('can be used as attribute on properties', function () {
    $reflection = new ReflectionClass(TestClassWithExample::class);
    $property = $reflection->getProperty('field');
    $attributes = $property->getAttributes(Example::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance())->toBeInstanceOf(Example::class)
        ->and($attributes[0]->newInstance()->value)->toBe('example value');
});

it('does not appear on properties without attribute', function () {
    $reflection = new ReflectionClass(TestClassWithExample::class);
    $property = $reflection->getProperty('fieldWithoutExample');
    $attributes = $property->getAttributes(Example::class);

    expect($attributes)->toBeEmpty();
});

class TestClassWithExample
{
    #[Example('example value')]
    public string $field;

    public string $fieldWithoutExample;
}
