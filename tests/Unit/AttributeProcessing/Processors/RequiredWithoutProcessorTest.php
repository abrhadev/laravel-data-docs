<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutProcessor;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;

beforeEach(function () {
    $this->processor = new RequiredWithoutProcessor();
});

it('states a single-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithout('email'), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>email</i></b> is not present.']);
});

it('states a multi-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithout(['email', 'phone']), $context);

    expect($context->descriptions)->toBe([
        'Required when any of <b><i>email</i></b>, <b><i>phone</i></b> is not present.',
    ]);
});

it('appends nothing when no field is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithout([]), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredWithout('email'), $context);

    expect($context->descriptions)->toBe([]);
});
