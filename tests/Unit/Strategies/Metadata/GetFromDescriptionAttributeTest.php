<?php

use Abrha\LaravelDataDocs\Attributes\Description;
use Abrha\LaravelDataDocs\Attributes\ResponseData;
use Abrha\LaravelDataDocs\Strategies\Metadata\GetFromDescriptionAttribute;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;

beforeEach(function () {
    $this->strategy = new GetFromDescriptionAttribute(new DocumentationConfig([]));
});

it('returns endpoint description from method attribute', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'show');

    $result = ($this->strategy)($endpointData);

    expect($result)->toBe(['description' => 'Fetch a user by id.']);
});

it('combines repeatable description attributes on a method', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'index');

    $result = ($this->strategy)($endpointData);

    expect($result)->toBe(['description' => 'Lists users. Paginated.']);
});

it('combines variadic strings on a single description attribute', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'store');

    $result = ($this->strategy)($endpointData);

    expect($result)->toBe(['description' => 'Creates a user. Returns the created resource.']);
});

it('returns null when the method has no description attribute', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'destroy');

    $result = ($this->strategy)($endpointData);

    expect($result)->toBeNull();
});

it('returns null when method reflection is missing', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = null;

    $result = ($this->strategy)($endpointData);

    expect($result)->toBeNull();
});

it('returns null when description attributes have no strings', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'empty');

    $result = ($this->strategy)($endpointData);

    expect($result)->toBeNull();
});

it('does not require ResponseData to be present', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'index');

    $result = ($this->strategy)($endpointData);

    expect($result['description'])->toBe('Lists users. Paginated.');
});

it('returns null when a description attribute cannot be instantiated', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'invalid');

    $result = ($this->strategy)($endpointData);

    expect($result)->toBeNull();
});

it('keeps the valid descriptions when another cannot be instantiated', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'mixed');

    expect(($this->strategy)($endpointData))->toBe(['description' => 'Valid.']);
});

it('reads a subclass of Description on a method', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(GetFromDescriptionAttributeTestController::class, 'subclassed');

    expect(($this->strategy)($endpointData))->toBe(['description' => 'From a subclass.']);
});

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class GetFromDescriptionAttributeTestDescription extends Description {}

class GetFromDescriptionAttributeTestController
{
    #[Description('Valid.')]
    #[Description(['bad'])]
    public function mixed() {}

    #[GetFromDescriptionAttributeTestDescription('From a subclass.')]
    public function subclassed() {}

    #[Description('Fetch a user by id.')]
    #[ResponseData(GetFromDescriptionAttributeTestResponse::class)]
    public function show() {}

    #[Description('Lists users.')]
    #[Description('Paginated.')]
    public function index() {}

    #[Description('Creates a user.', 'Returns the created resource.')]
    public function store() {}

    #[Description]
    public function empty() {}

    public function destroy() {}

    #[Description(['not', 'a', 'string'])]
    public function invalid() {}
}

class GetFromDescriptionAttributeTestResponse {}
