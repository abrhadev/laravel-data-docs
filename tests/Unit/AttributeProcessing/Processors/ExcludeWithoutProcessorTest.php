<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithoutProcessor;
use Spatie\LaravelData\Attributes\Validation\ExcludeWithout;
use Spatie\LaravelData\Support\Validation\References\FieldReference;

beforeEach(function () {
    $this->processor = new ExcludeWithoutProcessor();
});

it('states the absence condition for a field given by name', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWithout('country'), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input when <b><i>country</i></b> is not present.',
    ]);
});

it('states the absence condition for a field given as a reference', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWithout(new FieldReference('country')), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input when <b><i>country</i></b> is not present.',
    ]);
});

it('states the absence condition for several fields', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWithout('a,b'), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input when any of <b><i>a</i></b>, <b><i>b</i></b> is not present.',
    ]);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeWithout('country'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
