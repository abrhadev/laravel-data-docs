<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeProcessor;
use Illuminate\Validation\Rules\ExcludeIf;
use Spatie\LaravelData\Attributes\Validation\Exclude;

beforeEach(function () {
    $this->processor = new ExcludeProcessor();
});

it('states an unconditional exclusion', function () {
    $context = conditionContext();

    $this->processor->process(new Exclude(), $context);

    expect($context->descriptions)->toBe(['Not validated, and removed from the validated input.']);
});

it('states an exclusion whose wrapped rule cannot be read as conditional', function () {
    $context = conditionContext();

    $this->processor->process(new Exclude(new ExcludeIf(true)), $context);

    expect($context->descriptions)->toBe(['Not validated, and removed from the validated input, in some requests.']);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Exclude(), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
