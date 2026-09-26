<?php

use Abrha\LaravelDataDocs\Attributes\ResponseData;
use Abrha\LaravelDataDocs\Strategies\ResponseDataStrategy;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;

function responseDataEndpoint(string $action): ExtractedEndpointData
{
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(ResponseDataStrategyTestController::class, $action);

    return $endpointData;
}

beforeEach(function () {
    $this->strategy = new ResponseDataStrategy(new DocumentationConfig([]));
});

it('documents the fields of the declared response DTO', function () {
    $result = ($this->strategy)(responseDataEndpoint('show'));

    expect(array_column($result, 'name'))->toBe(['id', 'name', 'display_name']);
});

it('names a mapped response field by its output name, not its input name', function () {
    $names = array_column(($this->strategy)(responseDataEndpoint('show')), 'name');

    expect($names)->toContain('display_name')
        ->not->toContain('dn_in')
        ->not->toContain('displayName');
});

it('reads a subclass of ResponseData on a method', function () {
    $result = ($this->strategy)(responseDataEndpoint('subclassAttribute'));

    expect(array_column($result, 'name'))->toBe(['id', 'name', 'display_name']);
});

it('returns null when the method declares no response DTO', function () {
    expect(($this->strategy)(responseDataEndpoint('withoutAttribute')))->toBeNull();
});

it('swallows a ResponseData attribute that cannot be instantiated', function () {
    // #[ResponseData] with no argument makes newInstance() throw an
    // ArgumentCountError; the strategy must answer null, not fail the build.
    expect(($this->strategy)(responseDataEndpoint('invalidAttribute')))->toBeNull();
});

it('returns no fields for a response DTO class that does not exist', function () {
    expect(($this->strategy)(responseDataEndpoint('missingClass')))->toBe([]);
});

it('returns no fields for a response class that is not a Data object', function () {
    expect(($this->strategy)(responseDataEndpoint('plainClass')))->toBe([]);
});

it('returns null when the endpoint has no method', function () {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = null;

    expect(($this->strategy)($endpointData))->toBeNull();
});

class ResponseDataStrategyTestPlain
{
    public function __construct(
        public int $id,
    ) {}
}

class ResponseDataStrategyTestResponse extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        #[MapOutputName('display_name'), MapInputName('dn_in')]
        public string $displayName,
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class ResponseDataStrategyTestResponseData extends ResponseData {}

class ResponseDataStrategyTestController
{
    #[ResponseData(ResponseDataStrategyTestResponse::class)]
    public function show(): void {}

    public function withoutAttribute(): void {}

    #[ResponseData]
    public function invalidAttribute(): void {}

    #[ResponseData('App\\Data\\DoesNotExist')]
    public function missingClass(): void {}

    #[ResponseData(ResponseDataStrategyTestPlain::class)]
    public function plainClass(): void {}

    #[ResponseDataStrategyTestResponseData(ResponseDataStrategyTestResponse::class)]
    public function subclassAttribute(): void {}
}
