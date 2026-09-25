<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithProcessor;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;

beforeEach(function () {
    $this->processor = new RequiredWithProcessor();
});

it('states a single-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWith('card_number'), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>card_number</i></b> is present.']);
});

it('states a multi-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWith(['card_number', 'card_expiry']), $context);

    expect($context->descriptions)->toBe([
        'Required when any of <b><i>card_number</i></b>, <b><i>card_expiry</i></b> is present.',
    ]);
});

it('appends nothing when no field is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWith([]), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredWith('card_number'), $context);

    expect($context->descriptions)->toBe([]);
});
