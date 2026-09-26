<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DifferentProcessor;
use Spatie\LaravelData\Attributes\Validation\Different;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

beforeEach(function () {
    $this->processor = new DifferentProcessor();
});

it('names a field given as a string', function () {
    $context = conditionContext();

    $this->processor->process(new Different('email'), $context);

    expect($context->descriptions)->toBe(['Must differ from the value of <b><i>email</i></b>.']);
});

it('names a field given as a field reference', function () {
    $context = conditionContext();

    $this->processor->process(new Different(new FieldReference('email')), $context);

    expect($context->descriptions)->toBe(['Must differ from the value of <b><i>email</i></b>.']);
});

it('names each field of a comma-joined list', function () {
    $context = conditionContext();

    $this->processor->process(new Different('a,b'), $context);

    expect($context->descriptions)->toBe(['Must differ from the value of each of <b><i>a</i></b>, <b><i>b</i></b>.']);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Different('email'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});
