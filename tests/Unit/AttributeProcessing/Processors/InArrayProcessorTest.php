<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\InArrayProcessor;
use Spatie\LaravelData\Attributes\Validation\InArray;

beforeEach(function () {
    $this->processor = new InArrayProcessor();
});

it('states membership for a trailing wildcard and strips it for display', function () {
    $context = conditionContext();

    $this->processor->process(new InArray('tags.*'), $context);

    expect($context->descriptions)->toBe(['Must be one of the values submitted in <b><i>tags</i></b>.']);
});

it('states membership for an inner wildcard and shows it as written', function () {
    $context = conditionContext();

    $this->processor->process(new InArray('tags.*.id'), $context);

    expect($context->descriptions)->toBe(['Must be one of the values submitted in <b><i>tags.*.id</i></b>.']);
});

it('states equality for a bare reference, which in_array compares by key', function () {
    $context = conditionContext();

    $this->processor->process(new InArray('tags'), $context);

    expect($context->descriptions)->toBe(['Must equal the value of <b><i>tags</i></b>.']);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new InArray('tags.*'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});
