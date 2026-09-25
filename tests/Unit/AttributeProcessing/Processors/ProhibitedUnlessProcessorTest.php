<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedUnlessProcessor;
use Spatie\LaravelData\Attributes\Validation\ProhibitedUnless;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new ProhibitedUnlessProcessor();
});

it('states a single-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('role', 'admin'), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent unless <b><i>role</i></b> is <code>admin</code>; the request is rejected if it is.',
    ]);
});

it('states a multi-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('role', 'admin', 'owner'), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent unless <b><i>role</i></b> is one of: <code>admin</code>, <code>owner</code>; the request is rejected if it is.',
    ]);
});

it('renders a backed enum by its case name and backing value', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('account_type', ConditionAccountType::Charity), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent unless <b><i>account_type</i></b> is <code>Charity</code> (charity); the request is rejected if it is.',
    ]);
});

it('renders a null value as null', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('role', [null]), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent unless <b><i>role</i></b> is <code>null</code>; the request is rejected if it is.',
    ]);
});

it('renders boolean values as true and false', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('verified', [true, false]), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent unless <b><i>verified</i></b> is one of: <code>true</code>, <code>false</code>; the request is rejected if it is.',
    ]);
});

it('degrades to a value-free sentence for an external reference', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('role', new RouteParameterReference('tier')), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent unless <b><i>role</i></b> has certain values; the request is rejected if it is.',
    ]);
});

it('appends nothing when no value is given', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('role'), $context);

    expect($context->descriptions)->toBe([]);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedUnless('role', 'admin'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
