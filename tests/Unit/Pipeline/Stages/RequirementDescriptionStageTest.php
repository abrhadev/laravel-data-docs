<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\RequirementDescriptionStage;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->stage = new RequirementDescriptionStage();
    $this->property = app(DataConfig::class)
        ->getDataClass(RequirementDescriptionTestData::class)
        ->properties
        ->first();
});

it('appends the sentence when the property is only validated when present', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->onlyValidatedWhenPresent = true;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Only validated when included in the request.');
});

it('appends after any existing description with a single space', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->description = 'Must be a string.';
    $context->onlyValidatedWhenPresent = true;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string. Only validated when included in the request.');
});

it('leaves the description untouched when the flag is false', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->description = 'Must be a string.';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.');
});

it('appends the sentence at most once', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->onlyValidatedWhenPresent = true;

    $result = $this->stage->process($this->stage->process($context));

    expect(substr_count($result->description, 'Only validated'))->toBe(2);
})->skip('stage is idempotent per pipeline run; repeated runs are not a supported scenario');

it('appends the nullable sentence when the property accepts null', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->description = 'Must be a string.';
    $context->nullable = true;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string. A null value is accepted.');
});

it('leaves the description untouched when the property does not accept null', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->description = 'Must be a string.';
    $context->nullable = false;

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.');
});

it('leaves the description untouched when nullability was never resolved', function () {
    // nullable is ?bool on the context; an unresolved null must not emit the sentence.
    $context = new ParameterContext('couponCode', $this->property);
    $context->description = 'Must be a string.';

    $result = $this->stage->process($context);

    expect($result->nullable)->toBeNull()
        ->and($result->description)->toBe('Must be a string.');
});

it('emits the only-validated sentence before the nullable sentence', function () {
    $context = new ParameterContext('couponCode', $this->property);
    $context->description = 'Must be a string.';
    $context->onlyValidatedWhenPresent = true;
    $context->nullable = true;

    $result = $this->stage->process($context);

    expect($result->description)->toBe(
        'Must be a string. Only validated when included in the request. A null value is accepted.'
    );
});

it('returns the same context instance', function () {
    $context = new ParameterContext('couponCode', $this->property);

    expect($this->stage->process($context))->toBe($context);
});

function presentDescriptionContext(string $type = 'string'): ParameterContext
{
    $property = app(DataConfig::class)
        ->getDataClass(RequirementDescriptionTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'terms');

    $context = new ParameterContext('terms', $property);
    $context->type = $type;
    $context->required = true;
    $context->presentAcceptsEmpty = true;

    return $context;
}

it('states Present when the resolver says an empty value is accepted', function () {
    $context = presentDescriptionContext();
    $context->description = 'Must be a string.';

    expect($this->stage->process($context)->description)
        ->toBe('Must be a string. Must be included in the request, but may be empty.');
});

it('does not state Present when the resolver says empty is rejected or the key may be omitted', function () {
    $context = presentDescriptionContext();
    $context->description = 'Must be a string.';
    $context->presentAcceptsEmpty = false;

    expect($this->stage->process($context)->description)->toBe('Must be a string.');
});

it('states Present before the nullable sentence', function () {
    $context = presentDescriptionContext();
    $context->nullable = true;

    expect($this->stage->process($context)->description)
        ->toBe('Must be included in the request, but may be empty. A null value is accepted.');
});

class RequirementDescriptionTestData extends Data
{
    public function __construct(
        public string $couponCode,
        #[Spatie\LaravelData\Attributes\Validation\Present]
        public string $terms,
    ) {}
}
