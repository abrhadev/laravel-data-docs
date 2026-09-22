<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataConfig;

function reconcileThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ReconciliationTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

it('AC1: publishes a required attribute over a type that admits null', function () {
    $context = reconcileThroughPipeline('email');

    expect($context->required)->toBeTrue()
        ->and($context->nullable)->toBeTrue();
});

it('AC2: keeps a nullable-attributed property required', function () {
    $context = reconcileThroughPipeline('middleName');

    expect($context->nullable)->toBeTrue()
        ->and($context->required)->toBeTrue();
});

it('AC3: publishes a sometimes-attributed property as optional and says so', function () {
    $context = reconcileThroughPipeline('couponCode');

    expect($context->required)->toBeFalse()
        ->and($context->description)->toContain('Only validated when included in the request.');
});

it('AC4: the reconciled status survives the whole pipeline run', function () {
    // Every stage after RequiredStage must leave the reconciled values alone.
    $context = reconcileThroughPipeline('email');

    expect($context->toParameter()->required)->toBeTrue()
        ->and($context->toParameter()->nullable)->toBeTrue();
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
