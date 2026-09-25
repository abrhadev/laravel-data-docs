<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithAllProcessor;
use Spatie\LaravelData\Attributes\Validation\RequiredWithAll;

beforeEach(function () {
    $this->processor = new RequiredWithAllProcessor();
});

it('states a single-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithAll('shipping_street'), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>shipping_street</i></b> is present.']);
});

it('states a two-field condition as both-and', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithAll(['shipping_street', 'shipping_country']), $context);

    expect($context->descriptions)->toBe([
        'Required when both <b><i>shipping_street</i></b> and <b><i>shipping_country</i></b> are present.',
    ]);
});

it('states a three-field condition as all-of', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithAll(['a', 'b', 'c']), $context);

    expect($context->descriptions)->toBe([
        'Required when all of <b><i>a</i></b>, <b><i>b</i></b>, <b><i>c</i></b> are present.',
    ]);
});

it('appends nothing when no field is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithAll([]), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredWithAll(['a', 'b']), $context);

    expect($context->descriptions)->toBe([]);
});
