<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SameProcessor;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

beforeEach(function () {
    $this->processor = new SameProcessor();
});

it('names a field given as a string', function () {
    $context = conditionContext();

    $this->processor->process(new Same('email'), $context);

    expect($context->descriptions)->toBe(['Must match the value of <b><i>email</i></b>.']);
});

it('names a field given as a field reference', function () {
    $context = conditionContext();

    $this->processor->process(new Same(new FieldReference('email')), $context);

    expect($context->descriptions)->toBe(['Must match the value of <b><i>email</i></b>.']);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Same('email'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});
