<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitsProcessor;
use Spatie\LaravelData\Attributes\Validation\Prohibits;

beforeEach(function () {
    $this->processor = new ProhibitsProcessor();
});

it('states that one other field is forbidden', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibits('partial_amount'), $context);

    expect($context->descriptions)->toBe([
        'Sending this field forbids sending <b><i>partial_amount</i></b>; the request is rejected if both are sent.',
    ]);
});

it('joins two forbidden fields with and', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibits(['partial_amount', 'refund_items']), $context);

    expect($context->descriptions)->toBe([
        'Sending this field forbids sending <b><i>partial_amount</i></b> and <b><i>refund_items</i></b>; the request is rejected if any of them is also sent.',
    ]);
});

it('joins three forbidden fields as a list', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibits(['a', 'b', 'c']), $context);

    expect($context->descriptions)->toBe([
        'Sending this field forbids sending <b><i>a</i></b>, <b><i>b</i></b> and <b><i>c</i></b>; the request is rejected if any of them is also sent.',
    ]);
});

it('appends nothing and does not throw when no field is given', function () {
    // Upstream leaves $fields uninitialised here, so parameters() throws.
    $context = conditionContext();

    $this->processor->process(new Prohibits(), $context);

    expect($context->descriptions)->toBe([]);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Prohibits('partial_amount'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
