<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\InProcessor;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new InProcessor();
});

it('records the declared values and leaves the sentence to the pipeline', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new In(['pending', 'shipped']), $context);

    expect($context->allowedValues)->toBe(['pending', 'shipped'])
        ->and($context->descriptions)->toBe([]);
});

it('reads the values of an In built from a rule string', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(In::create('a', 'b'), $context);

    expect($context->allowedValues)->toBe(['a', 'b']);
});

it('documents nothing when a value is an external reference', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new In(['a', new RouteParameterReference('status')]), $context);

    expect($context->allowedValues)->toBeNull()
        ->and($context->descriptions)->toBe([]);
});

it('keeps only the values a numeric field can have', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new In(['1', '2.5', 'abc', '-3']), $context);

    expect($context->allowedValues)->toBe(['1', '-3'])
        ->and($context->allowedValueList())->toBe([1, -3]);
});

it('leaves out values an earlier NotIn rejects', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->excludedValues = ['cancelled'];

    $this->processor->process(new In(['pending', 'cancelled']), $context);

    expect($context->allowedValues)->toBe(['pending']);
});

it('keeps rejected values in an array field\'s item set', function () {
    $context = conditionContext();
    $context->type = 'string[]';
    $context->excludedValues = ['admin'];

    $this->processor->process(new In(['admin', 'bob']), $context);

    expect($context->allowedValues)->toBe(['admin', 'bob']);
});

it('narrows enum cases on an array field too, since #[In] on an array requires every item to be in the set', function () {
    $context = conditionContext();
    $context->type = 'string[]';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ConditionAccountType::cases());

    $this->processor->process(new In(['business']), $context);

    expect($context->enumInfo?->toArray())->toBe(['business']);
});

it('writes nothing but the value sets', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new InProcessor())->process(new In(['a', 'b']), $context);

    expect($context->descriptions)->toBe([])
        ->and([$context->pattern, $context->format, $context->example, $context->minLength, $context->maxLength, $context->minimum, $context->maximum])
        ->toBe([null, null, null, null, null, null, null]);
});
