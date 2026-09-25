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

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Different('email'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});
