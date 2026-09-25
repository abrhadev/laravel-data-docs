<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedProcessor;
use Illuminate\Validation\Rules\ProhibitedIf;
use Spatie\LaravelData\Attributes\Validation\Prohibited;

beforeEach(function () {
    $this->processor = new ProhibitedProcessor();
});

it('states an unconditional prohibition', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibited(), $context);

    expect($context->descriptions)->toBe(['Must not be sent; the request is rejected if it is.']);
});

it('states a prohibition whose wrapped rule cannot be read as conditional', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibited(new ProhibitedIf(true)), $context);

    expect($context->descriptions)->toBe(['Must not be sent in some requests; the request is rejected if it is.']);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibited(), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
