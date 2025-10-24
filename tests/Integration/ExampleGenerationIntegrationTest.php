<?php

use Abrha\LaravelDataDocs\Attributes\Example;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

it('generates examples through complete pipeline', function () {
    $generator = new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class));
    $parameterObjects = $generator(ExampleIntegrationTestData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    expect($parameters)->toHaveCount(7)
        ->and($parameters['email']['name'])->toBe('email')
        ->and($parameters['email']['example'])->toBeString()
        ->and($parameters['email']['example'])->toMatch('/^[^@]+@[^@]+\.[^@]+$/')
        ->and($parameters['age']['name'])->toBe('age')
        ->and($parameters['age']['example'])->toBeInt()
        ->and($parameters['age']['example'])->toBeGreaterThanOrEqual(18)
        ->and($parameters['age']['example'])->toBeLessThanOrEqual(100)
        ->and($parameters['score']['name'])->toBe('score')
        ->and($parameters['score']['example'])->toBeNumeric()
        ->and($parameters['score']['example'])->toBeGreaterThanOrEqual(0)
        ->and($parameters['score']['example'])->toBeLessThanOrEqual(100)
        ->and($parameters['isActive']['name'])->toBe('isActive')
        ->and($parameters['isActive']['example'])->toBeBool()
        ->and($parameters['tags']['name'])->toBe('tags')
        ->and($parameters['tags']['example'])->toBeArray()
        ->and(count($parameters['tags']['example']))->toBeGreaterThan(0)
        ->and($parameters['customExample']['name'])->toBe('customExample')
        ->and($parameters['customExample']['example'])->toBe('my custom value')
        ->and($parameters['username']['name'])->toBe('username')
        ->and($parameters['username']['example'])->toBeString();
});

it('respects manual example attribute over auto-generation', function () {
    $generator = new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class));
    $parameterObjects = $generator(ExampleIntegrationTestData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    $customParam = collect($parameters)->firstWhere('name', 'customExample');

    expect($customParam['example'])->toBe('my custom value');
});

it('generates constraint-aware examples', function () {
    $generator = new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class));
    $parameterObjects = $generator(ExampleIntegrationTestData::class);
    $parameters = array_map(fn($param) => $param->toArray(), $parameterObjects);

    $ageParam = collect($parameters)->firstWhere('name', 'age');

    expect($ageParam['example'])->toBeGreaterThanOrEqual(18)
        ->and($ageParam['example'])->toBeLessThanOrEqual(100);
});

class ExampleIntegrationTestData extends Data
{
    public function __construct(
        #[Email]
        public string $email,
        #[Between(18, 100)]
        public int $age,
        #[Min(0), Max(100)]
        public float $score,
        public bool $isActive,
        public array $tags,
        #[Example('my custom value')]
        public string $customExample,
        public string $username,
    ) {}
}
