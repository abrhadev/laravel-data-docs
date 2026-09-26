<?php

use Abrha\LaravelDataDocs\OpenApi\ExtendedOpenApiGenerator;
use Abrha\LaravelDataDocs\Strategies\BodyParameters\GetFromRequestDTOStrategy as NullableArrayBodyStrategy;
use Illuminate\Support\Facades\Validator;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Camel\Extraction\Parameter;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Knuckles\Scribe\Writing\OpenAPISpecWriter;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\Base31Generator;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\BaseGenerator;
use Spatie\LaravelData\Data;

class NullableArrayMoney {}

class NullableArrayRequestData extends Data
{
    public function __construct(
        /** @var NullableArrayMoney[]|null */
        public ?array $amounts,
        /** @var string[]|null */
        public ?array $tags,
        /** @var int[] */
        public array $counts,
        public ?string $note,
        public ?int $age,
        public ?bool $active,
    ) {}
}

class NullableArrayRequestController
{
    public function store(NullableArrayRequestData $data) {}
}

/** Whether a schema of the given OpenAPI version accepts a null value. */
function nullableArraySchemaAcceptsNull(array $schema, string $version): bool
{
    return $version === '3.1.0'
        ? in_array('null', (array) ($schema['type'] ?? null), true)
        : ($schema['nullable'] ?? false) === true;
}

function nullableArraySpec(string $version, array $endpointData): array
{
    $endpoint = OutputEndpointData::create([
        'httpMethods' => ['POST'],
        'uri'         => 'things',
        'metadata'    => ['title' => 'Create thing', 'authenticated' => false],
        ...$endpointData,
    ]);
    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => ['version' => $version, 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    return (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Things', 'description' => '', 'endpoints' => [$endpoint]],
    ]);
}

function nullableArrayBodyProperties(array $spec): array
{
    return $spec['paths']['/things']['post']['requestBody']['content']['application/json']['schema']['properties'];
}

it('marks a nullable array request field nullable on the array, not on its items', function (string $version, string|array $arrayType, array $items) {
    $properties = nullableArrayBodyProperties(nullableArraySpec($version, ['bodyParameters' => [
        'amounts' => [
            'name'        => 'amounts',
            'type'        => 'string[]',
            'required'    => false,
            'nullable'    => true,
            'description' => 'The amounts.',
            'example'     => ['12.50'],
            'custom'      => ['openAPI' => ['items' => ['pattern' => '^\d+\.\d{2}$'], 'minItems' => 1]],
        ],
    ]]));

    expect($properties['amounts'])->toMatchArray(['type' => $arrayType, 'minItems' => 1])
        ->and(nullableArraySchemaAcceptsNull($properties['amounts'], $version))->toBeTrue()
        ->and($properties['amounts']['items'])->toBe($items)
        ->and(nullableArraySchemaAcceptsNull($properties['amounts']['items'], $version))->toBeFalse();
})->with([
    '3.0' => ['3.0.3', 'array', ['type' => 'string', 'pattern' => '^\d+\.\d{2}$']],
    '3.1' => ['3.1.0', ['array', 'null'], ['type' => 'string', 'pattern' => '^\d+\.\d{2}$']],
]);

it('marks every item type of a nullable array field nullable on the array', function (string $version, string $type, array $items) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData(['name' => 'values', 'type' => $type, 'nullable' => true, 'description' => '']);

    expect(nullableArraySchemaAcceptsNull($field, $version))->toBeTrue()
        ->and($field['items'])->toBe($items);
})->with(['3.0.3', '3.1.0'])->with([
    'string'  => ['string[]', ['type' => 'string']],
    'integer' => ['integer[]', ['type' => 'integer']],
    'number'  => ['number[]', ['type' => 'number']],
    'boolean' => ['boolean[]', ['type' => 'boolean']],
    'object'  => ['object[]', ['type' => 'object']],
    'file'    => ['file[]', ['type' => 'string', 'format' => 'binary']],
]);

it('keeps the enum of a nullable array field\'s items and marks only the array nullable', function (string $version) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData([
        'name' => 'sizes', 'type' => 'string[]', 'nullable' => true, 'description' => '', 'enumValues' => ['s', 'm'],
    ]);

    expect(nullableArraySchemaAcceptsNull($field, $version))->toBeTrue()
        ->and($field['items'])->toBe(['type' => 'string', 'enum' => ['s', 'm']]);
})->with(['3.0.3', '3.1.0']);

it('marks only the outer array of a nullable nested array field nullable', function (string $version) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData([
        'name' => 'grid', 'type' => 'integer[][]', 'nullable' => true, 'description' => '', 'example' => [[1]],
    ]);

    expect(nullableArraySchemaAcceptsNull($field, $version))->toBeTrue()
        ->and(nullableArraySchemaAcceptsNull($field['items'], $version))->toBeFalse()
        ->and($field['items']['type'])->toBe('array')
        ->and($field['items']['items'])->toBe(['type' => 'integer']);
})->with(['3.0.3', '3.1.0']);

it('marks a nullable array field nullable on the array when Scribe passes a Parameter', function (string $version) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));
    $parameter = new Parameter(['name' => 'tags', 'type' => 'string[]', 'nullable' => true, 'description' => '']);

    $field = $generator->generateFieldData($parameter);

    expect(nullableArraySchemaAcceptsNull($field, $version))->toBeTrue()
        ->and($field['items'])->toBe(['type' => 'string'])
        ->and($parameter->nullable)->toBeTrue();
})->with(['3.0.3', '3.1.0']);

it('marks a nullable array nested in a request object nullable on the array', function (string $version) {
    $properties = nullableArrayBodyProperties(nullableArraySpec($version, ['bodyParameters' => [
        'lines' => [
            'name' => 'lines', 'type' => 'object[]', 'required' => true, 'nullable' => true, 'description' => '', 'example' => [['tags' => ['a']]],
        ],
        'lines[].tags' => [
            'name'        => 'lines[].tags',
            'type'        => 'string[]',
            'required'    => false,
            'nullable'    => true,
            'description' => '',
            'example'     => ['a'],
            'custom'      => ['openAPI' => ['items' => ['maxLength' => 5]]],
        ],
        'lines[].qty' => [
            'name' => 'lines[].qty', 'type' => 'integer', 'required' => true, 'nullable' => true, 'description' => '', 'example' => 2,
        ],
    ]]));
    $lines = $properties['lines'];
    $tags = $lines['items']['properties']['tags'];

    expect(nullableArraySchemaAcceptsNull($lines, $version))->toBeTrue()
        ->and(nullableArraySchemaAcceptsNull($lines['items'], $version))->toBeFalse()
        ->and(nullableArraySchemaAcceptsNull($tags, $version))->toBeTrue()
        ->and($tags['items'])->toBe(['type' => 'string', 'maxLength' => 5])
        ->and(nullableArraySchemaAcceptsNull($lines['items']['properties']['qty'], $version))->toBeTrue();
})->with(['3.0.3', '3.1.0']);

it('marks a nullable array query parameter nullable on the array', function (string $version) {
    $spec = nullableArraySpec($version, ['queryParameters' => [
        'ids' => ['name' => 'ids', 'type' => 'integer[]', 'required' => false, 'nullable' => true, 'description' => '', 'example' => [1]],
    ]]);
    $schema = $spec['paths']['/things']['post']['parameters'][0]['schema'];

    expect(nullableArraySchemaAcceptsNull($schema, $version))->toBeTrue()
        ->and($schema['items'])->toBe(['type' => 'integer']);
})->with(['3.0.3', '3.1.0']);

it('generates the same schema as Scribe for fields that are not nullable arrays', function (string $version, string $scribeGenerator, array $field) {
    $config = new DocumentationConfig(['openapi' => ['version' => $version]]);

    expect((new ExtendedOpenApiGenerator($config))->generateFieldData($field))
        ->toBe((new $scribeGenerator($config))->generateFieldData($field));
})->with([
    '3.0' => ['3.0.3', BaseGenerator::class],
    '3.1' => ['3.1.0', Base31Generator::class],
])->with([
    'nullable string'    => [['name' => 'note', 'type' => 'string', 'nullable' => true, 'description' => '', 'example' => 'x']],
    'nullable integer'   => [['name' => 'age', 'type' => 'integer', 'nullable' => true, 'description' => '', 'example' => 3]],
    'nullable number'    => [['name' => 'price', 'type' => 'number', 'nullable' => true, 'description' => '', 'example' => 1.5]],
    'nullable boolean'   => [['name' => 'active', 'type' => 'boolean', 'nullable' => true, 'description' => '', 'example' => true]],
    'nullable enum'      => [['name' => 'size', 'type' => 'string', 'nullable' => true, 'description' => '', 'enumValues' => ['s', 'm']]],
    'nullable object'    => [['name' => 'meta', 'type' => 'object', 'nullable' => true, 'description' => '', '__fields' => ['k' => ['name' => 'k', 'type' => 'string', 'required' => true, 'description' => '']]]],
    'nullable file'      => [['name' => 'avatar', 'type' => 'file', 'nullable' => true, 'description' => '']],
    'non-nullable array' => [['name' => 'tags', 'type' => 'string[]', 'nullable' => false, 'description' => '', 'example' => ['a']]],
]);

it('leaves a nullable array response field marked nullable on the array', function (string $version, array $expected) {
    $spec = nullableArraySpec($version, [
        'responses'      => [['status' => 200, 'content' => json_encode(['tags' => ['a']])]],
        'responseFields' => [
            'tags' => ['name' => 'tags', 'type' => 'string[]', 'nullable' => true, 'description' => '', 'custom' => ['openAPI' => ['items' => ['maxLength' => 5]]]],
        ],
    ]);

    expect($spec['paths']['/things']['post']['responses'][200]['content']['application/json']['schema']['properties']['tags'])
        ->toMatchArray($expected);
})->with([
    '3.0' => ['3.0.3', ['type' => 'array', 'nullable' => true, 'items' => ['type' => 'string', 'maxLength' => 5]]],
    '3.1' => ['3.1.0', ['type' => ['array', 'null'], 'items' => ['maxLength' => 5]]],
]);

it('publishes a nullable Data array property whose null Laravel accepts as nullable in the spec', function (string $version) {
    config()->set('data-docs.custom_types', [
        NullableArrayMoney::class . '[]' => ['type' => 'string[]', 'descriptions' => ['Money.'], 'pattern' => '^\d+\.\d{2}$'],
    ]);
    $endpoint = mock(ExtractedEndpointData::class);
    $endpoint->method = new ReflectionMethod(NullableArrayRequestController::class, 'store');
    $endpoint->httpMethods = ['POST'];
    $rules = NullableArrayRequestData::getValidationRules([]);

    for ($run = 0; $run < 20; $run++) {
        $body = (new NullableArrayBodyStrategy(new DocumentationConfig([])))($endpoint);
        $properties = nullableArrayBodyProperties(nullableArraySpec($version, ['bodyParameters' => $body]));
        $payload = array_map(fn(array $parameter) => $parameter['example'], $body);

        foreach (['amounts', 'tags', 'note', 'age', 'active'] as $name) {
            expect(Validator::make([...$payload, $name => null], $rules)->errors()->has($name))->toBeFalse()
                ->and(nullableArraySchemaAcceptsNull($properties[$name], $version))->toBeTrue();
        }

        expect(Validator::make([...$payload, 'counts' => null], $rules)->errors()->has('counts'))->toBeTrue()
            ->and(nullableArraySchemaAcceptsNull($properties['counts'], $version))->toBeFalse()
            ->and(nullableArraySchemaAcceptsNull($properties['amounts']['items'], $version))->toBeFalse()
            ->and(nullableArraySchemaAcceptsNull($properties['tags']['items'], $version))->toBeFalse()
            ->and($properties['amounts']['items'])->toMatchArray(['type' => 'string', 'pattern' => '^\d+\.\d{2}$']);

        foreach ($payload['amounts'] as $item) {
            expect($item)->toMatch('/^\d+\.\d{2}$/');
        }
    }
})->with(['3.0.3', '3.1.0']);
