<?php

use Abrha\LaravelDataDocs\OpenApi\ExtendedOpenApiGenerator;
use Knuckles\Camel\Output\OutputEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;

it('merges custom OpenAPI data when generating a single response value schema', function (string $version, array $expected) {
    $endpoint = OutputEndpointData::create([
        'httpMethods'    => ['GET'],
        'uri'            => 'stats',
        'metadata'       => ['title' => 'Stats', 'authenticated' => false],
        'responseFields' => [
            'code' => ['name' => 'code', 'type' => 'string', 'description' => 'The code.', 'custom' => ['openAPI' => ['pattern' => '^[a-z]+-\d+$', 'maxLength' => 8]]],
        ],
    ]);
    $generator = new ExtendedOpenApiGenerator(new DocumentationConfig(['openapi' => ['version' => $version]]));

    expect($generator->generateSchemaForResponseValue('ab-12', $endpoint, 'code'))
        ->toMatchArray(['type' => 'string', 'description' => 'The code.', 'pattern' => '^[a-z]+-\d+$', 'maxLength' => 8, ...$expected]);
})->with([
    '3.0' => ['3.0.3', ['example' => 'ab-12']],
    '3.1' => ['3.1.0', ['examples' => ['ab-12']]],
]);
