<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\PasswordProcessor;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new PasswordProcessor();
});

afterEach(function () {
    PasswordRule::$defaultCallback = null;
});

it('states the password rule and publishes its minimum', function (Password $attribute, string $sentence, int $minLength) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('password')
        ->and($context->descriptions)->toBe([$sentence])
        ->and($context->minLength)->toBe($minLength);
})->with([
    'default minimum'     => fn() => [new Password(), 'Must be a password of at least <code>12</code> characters.', 12],
    'every class'         => fn() => [new Password(min: 16, mixedCase: true, numbers: true, symbols: true), 'Must be a password of at least <code>16</code> characters containing at least one uppercase and one lowercase letter, at least one number and at least one symbol.', 16],
    'a minimum below one' => fn() => [new Password(min: 0), 'Must be a password of at least <code>1</code> character.', 1],
    'a wrapped rule'      => fn() => [new Password(rule: PasswordRule::min(9)->letters()), 'Must be a password of at least <code>9</code> characters containing at least one letter.', 9],
]);

it('publishes a note and no length bound when the maximum is below the minimum', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Password(rule: PasswordRule::min(20)->max(10)), $context);

    expect($context->descriptions)->toBe(['Note: must be a password of <code>20</code> to <code>10</code> characters, which no password can be, so any request that sends this field fails validation.'])
        ->and($context->minLength)->toBeNull()
        ->and($context->maxLength)->toBeNull();
});

it('reads the application default for default: true', function () {
    PasswordRule::defaults(fn() => PasswordRule::min(10)->numbers());
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Password(default: true), $context);

    expect($context->descriptions)->toBe(['Must be a password of at least <code>10</code> characters containing at least one number.'])
        ->and($context->minLength)->toBe(10);
});

it('keeps a stricter minimum already set', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->minLength = 20;

    $this->processor->process(new Password(), $context);

    expect($context->minLength)->toBe(20);
});

it('falls back to the generic sentence when the arguments cannot be read', function () {
    $context = conditionContext();
    $context->type = 'string';
    $unreadable = (new ReflectionClass(Password::class))->newInstanceWithoutConstructor();

    $this->processor->process($unreadable, $context);

    expect($context->format)->toBe('password')
        ->and($context->descriptions)->toBe(['Must be a valid password.'])
        ->and($context->minLength)->toBeNull();
});

it('falls back to the generic sentence for an argument resolved at request time', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Password(min: new RouteParameterReference('length')), $context);

    expect($context->format)->toBe('password')
        ->and($context->descriptions)->toBe(['Must be a valid password.'])
        ->and($context->minLength)->toBeNull();
});
