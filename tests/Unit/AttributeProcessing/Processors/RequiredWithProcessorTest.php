<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\RuleNormalizer;

beforeEach(function () {
    $this->processor = new RequiredWithProcessor();
});

it('states a single-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWith('card_number'), $context);

    expect($context->descriptions)->toBe(['Required when <b><i>card_number</i></b> is present.']);
});

it('states a multi-field condition', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWith(['card_number', 'card_expiry']), $context);

    expect($context->descriptions)->toBe([
        'Required when any of <b><i>card_number</i></b>, <b><i>card_expiry</i></b> is present.',
    ]);
});

it('appends nothing when no field is given', function () {
    $context = conditionContext();

    $this->processor->process(new RequiredWith([]), $context);

    expect($context->descriptions)->toBe([]);
});

it('appends nothing when the property also carries Present', function () {
    $context = conditionContext('suppressed');

    $this->processor->process(new RequiredWith('card_number'), $context);

    expect($context->descriptions)->toBe([]);
});

it('keeps the sentence when a later #[Rule] cannot be expanded', function () {
    $property = app(DataConfig::class)
        ->getDataClass(RequiredWithProcessorTestRuleData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'cardHolder');
    $context = new ParameterContext($property->name, $property);
    $attribute = $property->attributes->all(RequiredWith::class)[0];

    $this->mock(RuleNormalizer::class)
        ->shouldReceive('execute')
        ->andThrow(new RuntimeException('Unresolvable rule'));

    $this->processor->process($attribute, $context);

    expect($context->descriptions)->toBe(['Required when <b><i>card_number</i></b> is present.']);
});

class RequiredWithProcessorTestRuleData extends Data
{
    public function __construct(
        #[RequiredWith('card_number')]
        #[Rule('required')]
        public ?string $cardHolder,
    ) {}
}
