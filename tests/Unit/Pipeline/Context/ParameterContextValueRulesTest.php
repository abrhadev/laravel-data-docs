<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Support\DataProperty;

it('skips a value rule the validator cannot run', function () {
    $context = new ParameterContext('field', mock(DataProperty::class));
    $context->type = 'string';
    $context->valueRules = ['no_such_rule', 'email'];

    expect($context->matchesValueRules('jane@example.com'))->toBeTrue()
        ->and($context->matchesValueRules('not an email'))->toBeFalse();
});

it('leaves out an allowed value of a numeric field that is not a number', function (string $type, bool $numericString) {
    $context = new ParameterContext('field', mock(DataProperty::class));
    $context->type = $type;
    $context->numericString = $numericString;
    $context->allowedValues = ['5', 'five'];

    expect($context->acceptedAllowedValues())->toBe(['5']);
})->with([
    'integer'        => ['integer', false],
    'number'         => ['number', false],
    'numeric string' => ['string', true],
]);

it('keeps every allowed value of an array field whose items are neither strings nor numbers', function () {
    $context = new ParameterContext('field', mock(DataProperty::class));
    $context->type = 'boolean[]';
    $context->itemSchema = ['minimum' => 10];
    $context->allowedValues = ['1', ''];

    expect($context->acceptedAllowedValues())->toBe(['1', '']);
});
