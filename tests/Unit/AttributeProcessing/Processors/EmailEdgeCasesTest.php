<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EmailProcessor;
use Spatie\LaravelData\Attributes\Validation\Email;

it('falls back to the generic sentence when a subclass reports a mode that is not a string', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new EmailProcessor())->process(new EmailEdgeCasesMixedModes(), $context);

    expect($context->format)->toBe('email')
        ->and($context->descriptions)->toBe(['Must be a valid email address.'])
        ->and($context->valueRules)->toBe(['email:strict']);
});

class EmailEdgeCasesMixedModes extends Email
{
    public function parameters(): array
    {
        return ['strict', 5];
    }
}
