<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedProcessor;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Attributes\Validation\Declined;

beforeEach(function () {
    $this->processor = new DeclinedProcessor();
});

it('lists the values Laravel compares against', function () {
    $context = conditionContext();

    $this->processor->process(new Declined(), $context);

    expect($context->descriptions)->toBe(['Must be sent as one of <code>no</code>, <code>off</code>, <code>0</code>, <code>"0"</code>, <code>false</code>, or <code>"false"</code>.']);
});

it('sets an example from the value set by type', function (?string $type, mixed $example) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Declined(), $context);

    expect($context->example)->toBe($example);
})->with([
    'boolean' => ['boolean', false],
    'integer' => ['integer', 0],
    'number'  => ['number', 0],
    'string'  => ['string', 'no'],
    'object'  => ['object', null],
]);

it('leaves an existing example untouched', function () {
    $context = conditionContext();
    $context->type = 'boolean';
    $context->example = 'given';

    $this->processor->process(new Declined(), $context);

    expect($context->example)->toBe('given');
});

it('leaves an enum property to example generation', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ConditionAccountType::cases());

    $this->processor->process(new Declined(), $context);

    expect($context->example)->toBeNull();
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Declined(), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});
