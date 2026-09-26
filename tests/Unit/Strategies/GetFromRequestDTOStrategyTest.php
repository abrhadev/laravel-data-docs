<?php

use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Abrha\LaravelDataDocs\Strategies\BodyParameters\GetFromRequestDTOStrategy as BodyStrategy;
use Abrha\LaravelDataDocs\Strategies\QueryParameters\GetFromRequestDTOStrategy as QueryStrategy;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Dto;

function requestDtoEndpoint(string $action, array $httpMethods): ExtractedEndpointData
{
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = new ReflectionMethod(RequestDtoStrategyTestController::class, $action);
    $endpointData->httpMethods = $httpMethods;

    return $endpointData;
}

it('contributes nothing when the method takes no Data object', function (string $strategy) {
    $result = (new $strategy(new DocumentationConfig([])))(requestDtoEndpoint('withoutDto', ['POST']));

    expect($result)->toBe([]);
})->with([BodyStrategy::class, QueryStrategy::class]);

it('contributes nothing when the endpoint has no method', function (string $strategy) {
    $endpointData = mock(ExtractedEndpointData::class);
    $endpointData->method = null;
    $endpointData->httpMethods = ['POST'];

    expect((new $strategy(new DocumentationConfig([])))($endpointData))->toBe([]);
})->with([BodyStrategy::class, QueryStrategy::class]);

it('documents a POST request DTO as body parameters', function () {
    $body = (new BodyStrategy(new DocumentationConfig([])))(requestDtoEndpoint('store', ['POST']));
    $query = (new QueryStrategy(new DocumentationConfig([])))(requestDtoEndpoint('store', ['POST']));

    expect(array_column($body, 'name'))->toBe(['name', 'email', 'nick_in'])
        ->and(array_column($query, 'name'))->toBe(['page']);
});

it('names a mapped body parameter by its input name, not its output name', function () {
    $names = array_column((new BodyStrategy(new DocumentationConfig([])))(requestDtoEndpoint('store', ['POST'])), 'name');

    expect($names)->toContain('nick_in')
        ->not->toContain('nick_out')
        ->not->toContain('nickname');
});

it('documents a Dto request argument as body parameters', function () {
    $body = (new BodyStrategy(new DocumentationConfig([])))(requestDtoEndpoint('storeDto', ['POST']));

    expect(array_column($body, 'name'))->toBe(['title', 'count']);
});

it('documents a GET request DTO without query attributes as query parameters', function () {
    $body = (new BodyStrategy(new DocumentationConfig([])))(requestDtoEndpoint('index', ['GET']));
    $query = (new QueryStrategy(new DocumentationConfig([])))(requestDtoEndpoint('index', ['GET']));

    expect($body)->toBe([])
        ->and(array_column($query, 'name'))->toBe(['search']);
});

it('returns the Scribe parameter shape without a location key', function () {
    $body = (new BodyStrategy(new DocumentationConfig([])))(requestDtoEndpoint('store', ['POST']));
    $parameter = array_values($body)[0];

    expect($parameter)->toHaveKeys(['name', 'required', 'type', 'nullable', 'description', 'example'])
        ->not->toHaveKey('location');
});

class RequestDtoStrategyStoreData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        #[QueryParameter]
        public ?int $page,
        #[MapInputName('nick_in'), MapOutputName('nick_out')]
        public string $nickname,
    ) {}
}

class RequestDtoStrategyDto extends Dto
{
    public function __construct(
        public string $title,
        public int $count,
    ) {}
}

class RequestDtoStrategyIndexData extends Data
{
    public function __construct(
        public ?string $search,
    ) {}
}

class RequestDtoStrategyTestController
{
    public function withoutDto(int $id): void {}

    public function store(RequestDtoStrategyStoreData $data): void {}

    public function index(RequestDtoStrategyIndexData $filters): void {}

    public function storeDto(RequestDtoStrategyDto $data): void {}
}
