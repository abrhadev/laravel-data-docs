<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedIfProcessor;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new ProhibitedIfProcessor();
});

it('states a single-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('plan', 'enterprise'), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent when <b><i>plan</i></b> is <code>enterprise</code>; the request is rejected if it is.',
    ]);
});

it('states a multi-value condition', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('plan', 'enterprise', 'partner'), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent when <b><i>plan</i></b> is one of: <code>enterprise</code>, <code>partner</code>; the request is rejected if it is.',
    ]);
});

it('renders a backed enum by its case name and backing value', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('account_type', ConditionAccountType::Business), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent when <b><i>account_type</i></b> is <code>Business</code> (business); the request is rejected if it is.',
    ]);
});

it('renders a null value as null', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('plan', [null]), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent when <b><i>plan</i></b> is <code>null</code>; the request is rejected if it is.',
    ]);
});

it('renders boolean values as true and false', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('active', [true, false]), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent when <b><i>active</i></b> is one of: <code>true</code>, <code>false</code>; the request is rejected if it is.',
    ]);
});

it('degrades to a value-free sentence for an external reference', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('plan', new RouteParameterReference('tier')), $context);

    expect($context->descriptions)->toBe([
        'Must not be sent when <b><i>plan</i></b> has certain values; the request is rejected if it is.',
    ]);
});

it('appends nothing when no value is given', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('plan'), $context);

    expect($context->descriptions)->toBe([]);
});

it('is not suppressed by Present, which does not displace a prohibition', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new ProhibitedIf('plan', 'enterprise'), $context);

    expect($context->descriptions)->toHaveCount(1);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new ProhibitedIf('plan', 'enterprise'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
