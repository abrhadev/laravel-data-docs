<?php

use Abrha\LaravelDataDocs\OpenApi\ExtendedOpenApiGenerator;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Knuckles\Scribe\Writing\OpenAPISpecWriter;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\Base31Generator;
use Knuckles\Scribe\Writing\OpenApiSpecGenerators\BaseGenerator;

function extendedOpenApiSpec(array $openapi): array
{
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['POST'],
        'uri'            => 'users',
        'metadata'       => ['title' => 'Create user', 'authenticated' => false],
        'bodyParameters' => [
            'name' => [
                'name'        => 'name',
                'type'        => 'string',
                'required'    => false,
                'nullable'    => true,
                'description' => 'The name.',
                'example'     => 'Jane',
                'custom'      => ['openAPI' => ['minLength' => 2, 'default' => 'Anon']],
            ],
        ],
    ]);

    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => [...$openapi, 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    return (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Users', 'description' => '', 'endpoints' => [$endpoint]],
    ]);
}

function extendedOpenApiNameSchema(array $spec): array
{
    return $spec['paths']['/users']['post']['requestBody']['content']['application/json']['schema']['properties']['name'];
}

it('merges custom OpenAPI data into an OpenAPI 3.0 field schema', function () {
    $schema = extendedOpenApiNameSchema(extendedOpenApiSpec(['version' => '3.0.3']));

    expect($schema)
        ->toMatchArray(['type' => 'string', 'nullable' => true, 'example' => 'Jane', 'minLength' => 2, 'default' => 'Anon']);
});

it('keeps the OpenAPI 3.1 field schema shape while merging custom OpenAPI data', function () {
    $schema = extendedOpenApiNameSchema(extendedOpenApiSpec(['version' => '3.1.0']));

    expect($schema)
        ->toMatchArray(['type' => ['string', 'null'], 'examples' => ['Jane'], 'minLength' => 2, 'default' => 'Anon'])
        ->not->toHaveKeys(['nullable', 'example']);
});

it('keeps the openapi overrides applied before it', function (string $version) {
    $spec = extendedOpenApiSpec([
        'version'   => $version,
        'overrides' => ['info.version' => '2.0.0', 'servers' => [['url' => 'https://api.example.test']]],
    ]);

    expect($spec['info']['version'])->toBe('2.0.0')
        ->and($spec['servers'])->toBe([['url' => 'https://api.example.test']]);
})->with(['3.0.3', '3.1.0']);

it('publishes exclusive bounds in the form each OpenAPI version defines', function (string $version, array $custom, array $expected, array $absent) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData([
        'name'   => 'quantity',
        'type'   => 'number',
        'custom' => ['openAPI' => $custom],
    ]);

    expect($field)->toMatchArray($expected)->not->toHaveKeys($absent);
})->with([
    '3.0 exclusive minimum'                 => ['3.0.3', ['exclusiveMinimum' => 0.5], ['minimum' => 0.5, 'exclusiveMinimum' => true], []],
    '3.0 exclusive maximum'                 => ['3.0.3', ['exclusiveMaximum' => 10], ['maximum' => 10, 'exclusiveMaximum' => true], []],
    '3.0 stricter exclusive than inclusive' => ['3.0.3', ['minimum' => 3, 'exclusiveMinimum' => 5], ['minimum' => 5, 'exclusiveMinimum' => true], []],
    '3.0 equal exclusive and inclusive'     => ['3.0.3', ['maximum' => 5, 'exclusiveMaximum' => 5], ['maximum' => 5, 'exclusiveMaximum' => true], []],
    '3.0 stricter inclusive than exclusive' => ['3.0.3', ['minimum' => 5, 'exclusiveMinimum' => 3, 'maximum' => 9], ['minimum' => 5, 'maximum' => 9], ['exclusiveMinimum']],
    '3.0 stricter inclusive maximum'        => ['3.0.3', ['maximum' => 5, 'exclusiveMaximum' => 8], ['maximum' => 5], ['exclusiveMaximum']],
    '3.1 keeps numeric exclusive bounds'    => ['3.1.0', ['minimum' => 3, 'exclusiveMinimum' => 5, 'exclusiveMaximum' => 10], ['minimum' => 3, 'exclusiveMinimum' => 5, 'exclusiveMaximum' => 10], []],
]);

it('publishes a pipeline exclusive bound as OpenAPI 3.0 defines it in the written spec', function () {
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['POST'],
        'uri'            => 'orders',
        'metadata'       => ['title' => 'Create order', 'authenticated' => false],
        'bodyParameters' => [
            'quantity' => [
                'name'     => 'quantity',
                'type'     => 'integer',
                'required' => true,
                'example'  => 7,
                'custom'   => ['openAPI' => ['exclusiveMinimum' => 5]],
            ],
        ],
    ]);
    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => ['version' => '3.0.3', 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    $spec = (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Orders', 'description' => '', 'endpoints' => [$endpoint]],
    ]);

    expect($spec['paths']['/orders']['post']['requestBody']['content']['application/json']['schema']['properties']['quantity'])
        ->toMatchArray(['type' => 'integer', 'minimum' => 5, 'exclusiveMinimum' => true]);
});

it('merges custom OpenAPI data into response field schemas', function (string $version, array $expected) {
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['GET'],
        'uri'            => 'stats',
        'metadata'       => ['title' => 'Stats', 'authenticated' => false],
        'responses'      => [['status' => 200, 'content' => json_encode(['total' => 7, 'code' => 'ab-12'])]],
        'responseFields' => [
            'total' => ['name' => 'total', 'type' => 'integer', 'description' => 'The total.', 'custom' => ['openAPI' => ['exclusiveMinimum' => 5]]],
            'code'  => ['name' => 'code', 'type' => 'string', 'description' => 'The code.', 'custom' => ['openAPI' => ['pattern' => '^[a-z]+-\d+$', 'maxLength' => 8]]],
        ],
    ]);
    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => ['version' => $version, 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    $spec = (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Stats', 'description' => '', 'endpoints' => [$endpoint]],
    ]);
    $properties = $spec['paths']['/stats']['get']['responses'][200]['content']['application/json']['schema']['properties'];

    expect($properties['total'])->toMatchArray($expected)
        ->and($properties['code'])->toMatchArray(['type' => 'string', 'pattern' => '^[a-z]+-\d+$', 'maxLength' => 8]);
})->with([
    '3.0' => ['3.0.3', ['type' => 'integer', 'minimum' => 5, 'exclusiveMinimum' => true]],
    '3.1' => ['3.1.0', ['type' => 'integer', 'exclusiveMinimum' => 5]],
]);

it('merges custom OpenAPI data into nested and array-item response fields', function (string $version) {
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['GET'],
        'uri'            => 'orders',
        'metadata'       => ['title' => 'Orders', 'authenticated' => false],
        'responses'      => [['status' => 200, 'content' => json_encode(['customer' => ['email' => 'a@example.com'], 'items' => [['qty' => 2]]])]],
        'responseFields' => [
            'customer.email' => ['name' => 'customer.email', 'type' => 'string', 'description' => '', 'custom' => ['openAPI' => ['format' => 'email']]],
            'items[].qty'    => ['name' => 'items[].qty', 'type' => 'integer', 'description' => '', 'custom' => ['openAPI' => ['minimum' => 1]]],
        ],
    ]);
    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => ['version' => $version, 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    $spec = (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Orders', 'description' => '', 'endpoints' => [$endpoint]],
    ]);
    $properties = $spec['paths']['/orders']['get']['responses'][200]['content']['application/json']['schema']['properties'];

    expect($properties['customer']['properties']['email'])->toMatchArray(['format' => 'email'])
        ->and($properties['items']['items']['properties']['qty'])->toMatchArray(['type' => 'integer', 'minimum' => 1]);
})->with(['3.0.3', '3.1.0']);

it('merges a custom type\'s item constraints into the items of a nullable array response field', function (string $version, array $expected) {
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['GET'],
        'uri'            => 'amounts',
        'metadata'       => ['title' => 'Amounts', 'authenticated' => false],
        'responses'      => [['status' => 200, 'content' => json_encode(['amounts' => ['12']])]],
        'responseFields' => [
            'amounts' => [
                'name'        => 'amounts',
                'type'        => 'string[]',
                'nullable'    => true,
                'description' => 'The amounts.',
                'custom'      => ['openAPI' => ['items' => ['pattern' => '^\d+$'], 'minItems' => 1]],
            ],
        ],
    ]);
    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => ['version' => $version, 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    $spec = (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Amounts', 'description' => '', 'endpoints' => [$endpoint]],
    ]);
    $amounts = $spec['paths']['/amounts']['get']['responses'][200]['content']['application/json']['schema']['properties']['amounts'];

    expect($amounts)->toMatchArray(['minItems' => 1])
        ->and($amounts['items'])->toBe($expected);
})->with([
    // Scribe writes no items for an OpenAPI 3.1 ['array', 'null'] schema, so the items are created.
    '3.0' => ['3.0.3', ['type' => 'string', 'pattern' => '^\d+$']],
    '3.1' => ['3.1.0', ['pattern' => '^\d+$']],
]);

it('merges custom OpenAPI data when generating a single field', function () {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => '3.0.3']]));

    $field = $generator->generateFieldData([
        'name'    => 'per_page',
        'type'    => 'integer',
        'example' => 15,
        'custom'  => ['openAPI' => ['default' => 15, 'minimum' => 1]],
    ]);

    expect($field)->toMatchArray(['type' => 'integer', 'example' => 15, 'default' => 15, 'minimum' => 1]);
});

it('generates the same field schema as the plain Scribe generator for a field without custom data', function (string $version, string $scribeGenerator, array $field) {
    $config = new DocumentationConfig(['openapi' => ['version' => $version]]);

    expect((new ExtendedOpenApiGenerator($config))->generateFieldData($field))
        ->toBe((new $scribeGenerator($config))->generateFieldData($field));
})->with([
    '3.0' => ['3.0.3', BaseGenerator::class],
    '3.1' => ['3.1.0', Base31Generator::class],
])->with([
    'string'  => [['name' => 'name', 'type' => 'string', 'required' => true, 'nullable' => true, 'description' => 'The name.', 'example' => 'Jane', 'enumValues' => ['Jane', 'John']]],
    'integer' => [['name' => 'age', 'type' => 'integer', 'required' => false, 'description' => '', 'example' => 3]],
    'file'    => [['name' => 'avatar', 'type' => 'file', 'required' => true, 'description' => 'The avatar.']],
    'array'   => [['name' => 'tags', 'type' => 'string[]', 'required' => false, 'description' => '', 'example' => ['a']]],
]);

it('merges a custom type\'s item constraints into the items of an array field', function (string $version) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData([
        'name'        => 'amounts',
        'type'        => 'string[]',
        'required'    => true,
        'description' => '',
        'example'     => ['12'],
        'custom'      => ['openAPI' => ['items' => ['pattern' => '^\\d+$', 'format' => 'decimal', 'minLength' => 1], 'minItems' => 2]],
    ]);

    expect($field)->toMatchArray(['type' => 'array', 'minItems' => 2])
        ->not->toHaveKeys(['pattern', 'format', 'minLength'])
        ->and($field['items'])->toMatchArray(['type' => 'string', 'pattern' => '^\\d+$', 'format' => 'decimal', 'minLength' => 1]);
})->with(['3.0.3', '3.1.0']);

it('keeps an attribute\'s constraint on an array field itself, where Laravel applies it', function (string $version) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData([
        'name'        => 'emails',
        'type'        => 'string[]',
        'required'    => true,
        'description' => '',
        'example'     => ['a@example.com'],
        'custom'      => ['openAPI' => ['format' => 'email']],
    ]);

    expect($field)->toMatchArray(['type' => 'array', 'format' => 'email'])
        ->and($field['items'])->not->toHaveKey('format');
})->with(['3.0.3', '3.1.0']);

it('lets the custom OpenAPI data override the schema Scribe generates for a file field', function (string $version) {
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    $field = $generator->generateFieldData([
        'name'        => 'avatar',
        'type'        => 'file',
        'required'    => true,
        'description' => 'The avatar.',
        'custom'      => ['openAPI' => ['format' => 'byte']],
    ]);

    expect($field)->toMatchArray(['type' => 'string', 'format' => 'byte', 'description' => 'The avatar.']);
})->with(['3.0.3', '3.1.0']);

it('merges custom OpenAPI data into a nested request body child', function (string $version) {
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['POST'],
        'uri'            => 'orders',
        'metadata'       => ['title' => 'Create order', 'authenticated' => false],
        'bodyParameters' => [
            'lines' => [
                'name'        => 'lines',
                'type'        => 'object[]',
                'required'    => true,
                'description' => 'The lines.',
                'example'     => [['qty' => 2]],
            ],
            'lines[].qty' => [
                'name'        => 'lines[].qty',
                'type'        => 'integer',
                'required'    => true,
                'description' => 'The quantity.',
                'example'     => 2,
                'custom'      => ['openAPI' => ['minimum' => 1, 'multipleOf' => 2]],
            ],
        ],
    ]);
    $config = new DocumentationConfig([
        'title'    => 'API',
        'base_url' => 'http://localhost',
        'openapi'  => ['version' => $version, 'generators' => [ExtendedOpenApiGenerator::class]],
    ]);

    $spec = (new OpenAPISpecWriter($config))->generateSpecContent([
        ['name' => 'Orders', 'description' => '', 'endpoints' => [$endpoint]],
    ]);
    $lines = $spec['paths']['/orders']['post']['requestBody']['content']['application/json']['schema']['properties']['lines'];

    expect($lines['items']['properties']['qty'])->toMatchArray(['type' => 'integer', 'minimum' => 1, 'multipleOf' => 2]);
})->with(['3.0.3', '3.1.0']);
