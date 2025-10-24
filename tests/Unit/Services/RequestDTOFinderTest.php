<?php

use Abrha\LaravelDataDocs\Services\RequestDTOFinder;
use Spatie\LaravelData\Data;

it('returns singleton instance', function () {
    $instance1 = RequestDTOFinder::getInstance();
    $instance2 = RequestDTOFinder::getInstance();

    expect($instance1)->toBe($instance2);
});

it('finds data class from method parameters', function () {
    $method = new ReflectionMethod(RequestDTOFinderTestController::class, 'store');
    $finder = RequestDTOFinder::getInstance();

    $result = $finder($method);

    expect($result)->toBeInstanceOf(ReflectionClass::class)
        ->and($result->getName())->toBe(TestRequestData::class);
});

it('returns null when no data class is found', function () {
    $method = new ReflectionMethod(RequestDTOFinderTestController::class, 'index');
    $finder = RequestDTOFinder::getInstance();

    $result = $finder($method);

    expect($result)->toBeNull();
});

it('ignores union types', function () {
    $method = new ReflectionMethod(RequestDTOFinderTestController::class, 'updateWithUnion');
    $finder = RequestDTOFinder::getInstance();

    $result = $finder($method);

    expect($result)->toBeNull();
});

it('ignores non-data classes', function () {
    $method = new ReflectionMethod(RequestDTOFinderTestController::class, 'other');
    $finder = RequestDTOFinder::getInstance();

    $result = $finder($method);

    expect($result)->toBeNull();
});

it('throws exception when trying to unserialize', function () {
    $instance = RequestDTOFinder::getInstance();
    $instance->__wakeup();
})->throws(Exception::class, 'Cannot unserialize singleton');

class TestRequestData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
    ) {}
}

class NonDataClass
{
    public function __construct(public string $value) {}
}

class RequestDTOFinderTestController
{
    public function store(TestRequestData $request)
    {
        return $request;
    }

    public function index(int $page = 1)
    {
        return [];
    }

    public function updateWithUnion(TestRequestData|array $data)
    {
        return $data;
    }

    public function other(NonDataClass $other)
    {
        return $other;
    }
}
