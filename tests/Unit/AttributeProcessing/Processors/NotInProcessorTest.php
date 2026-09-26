<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\NotInProcessor;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Attributes\Validation\NotIn;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new NotInProcessor();
});

it('records the rejected values and leaves the sentence to the pipeline', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new NotIn(['admin', 'root']), $context);

    expect($context->excludedValues)->toBe(['admin', 'root'])
        ->and($context->allowedValues)->toBeNull()
        ->and($context->descriptions)->toBe([]);
});

it('removes the rejected values from an earlier In set', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->allowedValues = ['pending', 'shipped', 'cancelled'];

    $this->processor->process(new NotIn(['cancelled']), $context);

    expect($context->allowedValues)->toBe(['pending', 'shipped']);
});

it('leaves an array field\'s item set alone', function () {
    $context = conditionContext();
    $context->type = 'string[]';
    $context->allowedValues = ['admin', 'bob'];

    $this->processor->process(new NotIn(['admin']), $context);

    expect($context->allowedValues)->toBe(['admin', 'bob'])
        ->and($context->excludedValues)->toBe(['admin']);
});

it('leaves an enum array field\'s case list alone, since #[NotIn] only rejects an array whose items are all forbidden', function () {
    $context = conditionContext();
    $context->type = 'string[]';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ConditionAccountType::cases());

    $this->processor->process(new NotIn(['business']), $context);

    expect($context->enumInfo?->toArray())->toBe(['business', 'charity']);
});

it('reads the values of a NotIn built from a rule string', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(NotIn::create('a', 'b'), $context);

    expect($context->excludedValues)->toBe(['a', 'b']);
});

it('documents nothing when a value is an external reference', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new NotIn(['a', new RouteParameterReference('name')]), $context);

    expect($context->excludedValues)->toBeNull();
});

it('writes nothing but the value sets', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new NotInProcessor())->process(new NotIn(['a', 'b']), $context);

    expect($context->descriptions)->toBe([])
        ->and([$context->pattern, $context->format, $context->example, $context->minLength, $context->maxLength, $context->minimum, $context->maximum])
        ->toBe([null, null, null, null, null, null, null]);
});
