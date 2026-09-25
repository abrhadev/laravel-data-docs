<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithProcessor;
use Spatie\LaravelData\Attributes\Validation\ExcludeWith;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

beforeEach(function () {
    $this->processor = new ExcludeWithProcessor();
});

it('states the presence condition for a field given by name', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWith('confirmed_total'), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input when <b><i>confirmed_total</i></b> is present.',
    ]);
});

it('states the presence condition for a field given as a reference', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWith(new FieldReference('confirmed_total')), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input when <b><i>confirmed_total</i></b> is present.',
    ]);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWith('confirmed_total'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
