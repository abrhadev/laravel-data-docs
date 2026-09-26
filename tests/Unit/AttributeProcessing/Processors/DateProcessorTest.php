<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateProcessor;
use Spatie\LaravelData\Attributes\Validation\Date;

beforeEach(function () {
    $this->processor = new DateProcessor();
});

it('states the date rule and publishes a date example format on a string', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Date(), $context);

    expect($context->descriptions)->toBe(['Must be a valid date.'])
        ->and($context->exampleFormat)->toBe('date');
});

it('publishes only a note on an array, which no date can be', function () {
    $context = conditionContext();
    $context->type = 'string[]';

    $this->processor->process(new Date(), $context);

    expect($context->descriptions)->toBe([
        'Note: must be a valid date, which no array can be, so any request that sends this field fails validation.',
    ])
        ->and($context->exampleFormat)->toBeNull();
});
