<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\QueryParameterProcessor;
use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Spatie\LaravelData\Support\DataProperty;

it('sets location to QUERY when processing QueryParameter attribute', function () {
    $processor = new QueryParameterProcessor();
    $attribute = new QueryParameter();
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);

    $processor->process($attribute, $context);

    expect($context->location)->toBe(ParameterLocation::QUERY);
});

it('overwrites existing location', function () {
    $processor = new QueryParameterProcessor();
    $attribute = new QueryParameter();
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->location = ParameterLocation::BODY;

    $processor->process($attribute, $context);

    expect($context->location)->toBe(ParameterLocation::QUERY);
});

it('preserves other context properties', function () {
    $processor = new QueryParameterProcessor();
    $attribute = new QueryParameter();
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->type = 'string';
    $context->required = true;
    $context->description = 'Test description';

    $processor->process($attribute, $context);

    expect($context->location)->toBe(ParameterLocation::QUERY)
        ->and($context->type)->toBe('string')
        ->and($context->required)->toBeTrue()
        ->and($context->description)->toBe('Test description');
});
