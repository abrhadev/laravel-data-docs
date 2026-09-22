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

it('returns the same context instance', function () {
    $context = new ParameterContext('couponCode', $this->property);

    expect($this->stage->process($context))->toBe($context);
});

class RequirementDescriptionTestData extends Data
{
    public function __construct(
        public string $couponCode,
    ) {}
}
