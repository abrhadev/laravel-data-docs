<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

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

it('records declined as a value rule, so an #[In] set is narrowed by it', function () {
    $context = conditionContext();

    $this->processor->process(new Declined(), $context);

    expect($context->valueRules)->toBe(['declined']);
});

it('leaves the example to an #[In] set on the field', function () {
    $property = app(DataConfig::class)->getDataClass(DeclinedProcessorTestInData::class)->properties->first();
    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';

    $this->processor->process(new Declined(), $context);

    expect($context->example)->toBeNull()
        ->and($context->valueRules)->toBe(['declined']);
});

it('keeps only the enum cases declined takes', function () {
    $context = conditionContext();
    $context->type = 'integer';
    $context->enumInfo = new EnumInfo(EnumType::INT_BACKED, DeclinedProcessorTestEnum::cases());

    $this->processor->process(new Declined(), $context);

    expect($context->enumInfo?->toArray())->toBe([0])
        ->and($context->example)->toBeNull();
});

it('leaves an empty allowed set when no enum case passes declined', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ConditionAccountType::cases());

    $this->processor->process(new Declined(), $context);

    expect($context->enumInfo)->toBeNull()
        ->and($context->allowedValues)->toBe([])
        ->and($context->example)->toBeNull();
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

enum DeclinedProcessorTestEnum: int
{
    case Off = 0;
    case On = 1;
}

class DeclinedProcessorTestInData extends Data
{
    public function __construct(
        #[In(['yes', 'no', 0, 1])]
        public string $value,
    ) {}
}
