<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredIfProcessor;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new RequiredIfProcessor();
});

it('states a single-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredIf('account_type', 'business'), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>account_type</i></b> is <code>business</code>.']);
});

it('states a multi-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredIf('account_type', 'business', 'charity'), $context);

    expect($context->descriptions)->toBe([
        'Required when <b><i>account_type</i></b> is one of: <code>business</code>, <code>charity</code>.',
    ]);
});

it('renders a backed enum by its backing value', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredIf('account_type', ConditionAccountType::Business), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>account_type</i></b> is <code>business</code>.']);
});

it('degrades to a value-free sentence for an external reference', function () {
    // RouteParameterReference::getValue() reads the current request and throws
    // when the parameter is absent, so dereferencing it at documentation time
    // would be both environment-dependent and a build failure.
    $context = conditionContext();

    $this->processor->process(new RequiredIf('account_type', new RouteParameterReference('tier')), $context);

    expect($context->descriptions)->toBe(['Required depending on the value of <b><i>account_type</i></b>.']);
});

it('appends nothing when no value is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredIf('account_type'), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredIf('account_type', 'business'), $context);

    expect($context->descriptions)->toBe([]);
});
