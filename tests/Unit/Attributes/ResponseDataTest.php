<?php

use Abrha\LaravelDataDocs\Attributes\ResponseData;

it('can be instantiated with dto class', function () {
    $responseData = new ResponseData(TestResponseDTO::class);

    expect($responseData)->toBeInstanceOf(ResponseData::class)
        ->and($responseData->dtoClass)->toBe(TestResponseDTO::class);
});

it('stores dto class as readonly property', function () {
    $responseData = new ResponseData('App\\Data\\UserResponse');

    expect($responseData->dtoClass)->toBe('App\\Data\\UserResponse');
});

it('can be used as attribute on methods', function () {
    $reflection = new ReflectionClass(TestController::class);
    $method = $reflection->getMethod('show');
    $attributes = $method->getAttributes(ResponseData::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance())->toBeInstanceOf(ResponseData::class)
        ->and($attributes[0]->newInstance()->dtoClass)->toBe(TestResponseDTO::class);
});

it('does not appear on methods without attribute', function () {
    $reflection = new ReflectionClass(TestController::class);
    $method = $reflection->getMethod('index');
    $attributes = $method->getAttributes(ResponseData::class);

    expect($attributes)->toBeEmpty();
});

class TestResponseDTO {}

class TestController
{
    #[ResponseData(TestResponseDTO::class)]
    public function show() {}

    public function index() {}
}
