<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedIfProcessor;
use Spatie\LaravelData\Attributes\Validation\DeclinedIf;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new DeclinedIfProcessor();
});

it('states the condition and the value set', function (mixed $value, string $rendered) {
    $context = conditionContext();

    $this->processor->process(new DeclinedIf('country', $value), $context);

    expect($context->descriptions)->toBe([
        'Must be declined when <b><i>country</i></b> is ' . $rendered . ', by sending one of <code>no</code>, <code>off</code>, <code>0</code>, <code>"0"</code>, <code>false</code>, or <code>"false"</code>.',
    ]);
})->with([
    'string'      => ['DE', '<code>DE</code>'],
    'true'        => [true, '<code>true</code>'],
    'integer'     => [3, '<code>3</code>'],
    'backed enum' => [ConditionAccountType::Business, '<code>Business</code> (business)'],
]);

it('degrades to a value-free sentence for an external reference', function () {
    $context = conditionContext();

    $this->processor->process(new DeclinedIf('country', new RouteParameterReference('region')), $context);

    expect($context->descriptions)->toBe([
        'Must be declined when <b><i>country</i></b> has certain values, by sending one of <code>no</code>, <code>off</code>, <code>0</code>, <code>"0"</code>, <code>false</code>, or <code>"false"</code>.',
    ]);
});

it('sets no example, because the condition is not known per property', function () {
    $context = conditionContext();
    $context->type = 'boolean';

    $this->processor->process(new DeclinedIf('country', 'DE'), $context);

    expect($context->example)->toBeNull();
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new DeclinedIf('country', 'DE'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});
