<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutAllProcessor;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;

beforeEach(function () {
    $this->processor = new RequiredWithoutAllProcessor();
});

it('states a single-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithoutAll('email'), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>email</i></b> is not present.']);
});

it('states a two-field condition as neither-nor', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithoutAll(['email', 'phone']), $context);

    expect($context->descriptions)->toBe([
        'Required when neither <b><i>email</i></b> nor <b><i>phone</i></b> is present.',
    ]);
});

it('states a three-field condition as none-of', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithoutAll(['email', 'phone', 'fax']), $context);

    expect($context->descriptions)->toBe([
        'Required when none of <b><i>email</i></b>, <b><i>phone</i></b>, <b><i>fax</i></b> are present.',
    ]);
});

it('appends nothing when no field is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWithoutAll([]), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredWithoutAll(['email', 'phone']), $context);

    expect($context->descriptions)->toBe([]);
});
