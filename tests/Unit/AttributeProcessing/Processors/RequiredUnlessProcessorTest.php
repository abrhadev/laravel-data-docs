<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredUnlessProcessor;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new RequiredUnlessProcessor();
});

it('states a single-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredUnless('status', 'approved'), $context);

    expect($context->descriptions)->toBe(['Required unless <b><i>status</i></b> is <code>approved</code>.']);
});

it('states a multi-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredUnless('status', 'approved', 'archived'), $context);

    expect($context->descriptions)->toBe([
        'Required unless <b><i>status</i></b> is one of: <code>approved</code>, <code>archived</code>.',
    ]);
});

it('renders a backed enum by its case name and backing value', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredUnless('status', ConditionAccountType::Charity), $context);

    expect($context->descriptions)->toBe(['Required unless <b><i>status</i></b> is <code>Charity</code> (charity).']);
});

it('renders a null comparison value', function () {
    // The attribute's signature permits null; it must render rather than vanish.
    $context = conditionContext();

    $this->processor->process(new RequiredUnless('status', null), $context);

    expect($context->descriptions)->toBe(['Required unless <b><i>status</i></b> is <code>""</code>.']);
});

it('degrades to a value-free sentence for an external reference', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredUnless('status', new RouteParameterReference('tier')), $context);

    expect($context->descriptions)->toBe(['Required depending on the value of <b><i>status</i></b>.']);
});

it('appends nothing when no value is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredUnless('status'), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredUnless('status', 'approved'), $context);

    expect($context->descriptions)->toBe([]);
});
