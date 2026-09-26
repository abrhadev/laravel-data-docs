<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EmailProcessor;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new EmailProcessor();
});

it('sets the email format and names each validation mode beyond rfc', function (Email $attribute, string $sentence) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('email')
        ->and($context->descriptions)->toBe([$sentence]);
})->with([
    'no mode'         => fn() => [new Email(), 'Must be a valid email address.'],
    'rfc only'        => fn() => [new Email('rfc'), 'Must be a valid email address.'],
    'one other mode'  => fn() => [new Email('dns'), 'Must be a valid email address that passes a DNS check that its domain has an MX, A or AAAA record.'],
    'two modes'       => fn() => [new Email('rfc', 'filter'), "Must be a valid email address that passes RFC 5322 validation and PHP's <code>FILTER_VALIDATE_EMAIL</code> filter."],
    'a repeated mode' => fn() => [new Email('dns', 'dns'), 'Must be a valid email address that passes a DNS check that its domain has an MX, A or AAAA record.'],
]);

it('falls back to the generic sentence when the modes cannot be read', function (Email $attribute) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->format)->toBe('email')
        ->and($context->descriptions)->toBe(['Must be a valid email address.']);
})->with([
    'every mode unknown'    => fn() => new Email('nope'),
    'an external reference' => fn() => new Email(new RouteParameterReference('mode')),
]);

it('records the offline email rule the allowed values are checked against', function (Email $attribute, array $rules) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process($attribute, $context);

    expect($context->valueRules)->toBe($rules);
})->with([
    // dns needs the network, and alone Laravel runs only the DNS check.
    'no mode'     => fn() => [new Email(), ['email:rfc']],
    'rfc and dns' => fn() => [new Email('rfc', 'dns'), ['email:rfc']],
    'strict'      => fn() => [new Email('strict', 'filter'), ['email:strict,filter']],
    'dns only'    => fn() => [new Email('dns'), []],
]);
