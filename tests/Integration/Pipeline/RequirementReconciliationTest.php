<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\AcceptedIf;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\DeclinedIf;
use Spatie\LaravelData\Attributes\Validation\Filled;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataConfig;

function reconcileThroughPipeline(string $property, string $dataClass = ReconciliationTestData::class): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass($dataClass)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

// AC1 and AC2 originally published nullable: true. Required is an implicit rule,
// so Laravel rejects null even alongside Nullable; both now publish false, and
// the null-rejection case below checks that against real validation.
it('AC1: publishes a required attribute over a type that admits null', function () {
    $context = reconcileThroughPipeline('email');

    expect($context->required)->toBeTrue()
        ->and($context->nullable)->toBeFalse()
        ->and($context->description)->not->toContain('A null value is accepted.');
});

it('AC2: keeps a nullable-attributed property required', function () {
    $context = reconcileThroughPipeline('middleName');

    expect($context->nullable)->toBeFalse()
        ->and($context->required)->toBeTrue();
});

it('publishes nullable only where a null value survives validation and construction', function (string $property) {
    // Validates and builds the Data object with the property set to null, so every
    // implicit rule and the PHP type are checked against Laravel and Laravel Data
    // rather than against a list kept here. Conditions reference fields absent
    // from the payload, so they do not hold: a conditional rule accepts null.
    try {
        NullAcceptanceTestData::validateAndCreate([...nullAcceptancePayload(), $property => null]);
        $accepted = true;
    } catch (Throwable) {
        $accepted = false;
    }

    expect(reconcileThroughPipeline($property, NullAcceptanceTestData::class)->nullable)->toBe($accepted);
})->with(fn() => array_map(
    fn(ReflectionParameter $parameter) => $parameter->getName(),
    (new ReflectionMethod(NullAcceptanceTestData::class, '__construct'))->getParameters(),
));

function nullAcceptancePayload(): array
{
    return [
        'plainNullable'              => 'a',
        'plain'                      => 'a',
        'nullableAttribute'          => 'a',
        'required'                   => 'a',
        'filled'                     => 'a',
        'accepted'                   => true,
        'declined'                   => false,
        'present'                    => 'a',
        'presentWithNullableRule'    => 'a',
        'requiredIf'                 => 'a',
        'requiredIfWithNullableRule' => 'a',
        'acceptedIf'                 => true,
        'declinedIf'                 => false,
    ];
}

it('publishes required only where validation rejects an omitted key', function (string $property) {
    // Same fixture, other half of the contract: required means the key must be
    // sent. Accepted and Declined fail on an absent key without being requiring
    // rules; conditions reference absent fields, so they do not hold.
    $rules = NullAcceptanceTestData::getValidationRules([]);
    $omittable = validator([], [$property => $rules[$property]])->passes();

    expect(reconcileThroughPipeline($property, NullAcceptanceTestData::class)->required)->toBe(!$omittable);
})->with(fn() => array_map(
    fn(ReflectionParameter $parameter) => $parameter->getName(),
    (new ReflectionMethod(NullAcceptanceTestData::class, '__construct'))->getParameters(),
));

it('AC3: publishes a sometimes-attributed property as optional and says so', function () {
    $context = reconcileThroughPipeline('couponCode');

    expect($context->required)->toBeFalse()
        ->and($context->description)->toContain('Only validated when included in the request.');
});

it('AC4: the reconciled status survives the whole pipeline run', function () {
    // Every stage after RequiredStage must leave the reconciled values alone.
    $context = reconcileThroughPipeline('email');

    expect($context->toParameter()->required)->toBeTrue()
        ->and($context->toParameter()->nullable)->toBeFalse();
});

it('AC5: leaves properties without a requirement attribute unchanged', function () {
    $nickname = reconcileThroughPipeline('nickname');
    $title = reconcileThroughPipeline('title');

    expect($nickname->required)->toBeFalse()
        ->and($nickname->nullable)->toBeTrue()
        ->and($nickname->description)->not->toContain('Only validated')
        ->and($title->required)->toBeTrue()
        ->and($title->nullable)->toBeFalse()
        ->and($title->description)->not->toContain('Only validated');
});

it('AC5b: says so for an Optional-typed property that carries no requirement attribute', function () {
    // The one attribute-free case whose description does change: SometimesRuleInferrer
    // adds Sometimes from type->isOptional, and the fallback derives the same flag.
    $note = reconcileThroughPipeline('note');

    expect($note->required)->toBeFalse()
        ->and($note->nullable)->toBeFalse()
        ->and($note->onlyValidatedWhenPresent)->toBeTrue()
        ->and($note->description)->toContain('Only validated when included in the request.');
});

it('AC6: publishes a defaulted required property as optional, per the recorded decision', function () {
    $context = reconcileThroughPipeline('status');

    expect($context->required)->toBeFalse();
});

it('produces identical output across repeated runs', function () {
    expect(reconcileThroughPipeline('couponCode')->description)
        ->toBe(reconcileThroughPipeline('couponCode')->description);
});

it('does not leak the requirement flag into the published parameter', function () {
    $parameter = reconcileThroughPipeline('couponCode')->toParameter()->toArray();

    expect($parameter)->not->toHaveKey('onlyValidatedWhenPresent')
        ->and($parameter['custom']['openAPI'] ?? [])->not->toHaveKey('onlyValidatedWhenPresent');
});

class NullAcceptanceTestData extends Data
{
    public function __construct(
        public ?string $plainNullable,
        public string $plain,
        #[Nullable]
        public string $nullableAttribute,
        #[Required]
        public ?string $required,
        #[Filled]
        public ?string $filled,
        #[Accepted]
        public ?bool $accepted,
        #[Declined]
        public ?bool $declined,
        #[Present]
        public ?string $present,
        #[Present, Nullable]
        public string $presentWithNullableRule,
        #[RequiredIf('account_type', 'business')]
        public ?string $requiredIf,
        #[Nullable, RequiredIf('account_type', 'business')]
        public string $requiredIfWithNullableRule,
        #[AcceptedIf('account_type', 'business')]
        public ?bool $acceptedIf,
        #[DeclinedIf('account_type', 'business')]
        public ?bool $declinedIf,
    ) {}
}

class ReconciliationTestData extends Data
{
    public function __construct(
        #[Required]
        public ?string $email,
        #[Nullable]
        public string $middleName,
        #[Sometimes]
        public string $couponCode,
        public ?string $nickname,
        public string $title,
        public string|Optional $note,
        #[Required]
        public string $status = 'draft',
    ) {}
}
