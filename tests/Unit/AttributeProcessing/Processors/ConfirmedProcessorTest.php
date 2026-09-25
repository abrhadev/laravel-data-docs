<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ConfirmedProcessor;
use Spatie\LaravelData\Attributes\Validation\Confirmed;

beforeEach(function () {
    $this->processor = new ConfirmedProcessor();
});

it('names the default companion in the sentence and on the context', function () {
    $context = conditionContext();

    $this->processor->process(new Confirmed(), $context);

    expect($context->descriptions)->toBe(['A matching <b><i>plain_confirmation</i></b> value must be sent with it.'])
        ->and($context->confirmationCompanion->name)->toBe('plain_confirmation');
});

it('records companion sentences that name the source field', function () {
    $context = conditionContext();

    $this->processor->process(new Confirmed(), $context);

    expect($context->confirmationCompanion->matchSentence)->toBe('Must match the value of <b><i>plain</i></b>.')
        ->and($context->confirmationCompanion->requiredWhenSentSentence)->toBe('Required when <b><i>plain</i></b> is sent.');
});

it('follows a companion name reported by parameters()', function () {
    $context = conditionContext();

    $this->processor->process(new ConfirmedProcessorTestNamedConfirmed(), $context);

    expect($context->confirmationCompanion->name)->toBe('pin_repeat')
        ->and($context->descriptions)->toBe(['A matching <b><i>pin_repeat</i></b> value must be sent with it.']);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Confirmed(), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});

class ConfirmedProcessorTestNamedConfirmed extends Confirmed
{
    public function parameters(): array
    {
        return ['pin_repeat'];
    }
}
