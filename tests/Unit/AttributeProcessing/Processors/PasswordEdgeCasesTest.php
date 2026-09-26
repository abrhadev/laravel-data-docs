<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\PasswordProcessor;
use Spatie\LaravelData\Attributes\Validation\Email;

it('states the generic sentence for an attribute that is not a Password', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new PasswordProcessor())->process(new Email(), $context);

    expect($context->format)->toBe('password')
        ->and($context->descriptions)->toBe(['Must be a valid password.'])
        ->and($context->minLength)->toBeNull();
});
