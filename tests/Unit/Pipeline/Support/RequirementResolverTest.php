<?php

use Abrha\LaravelDataDocs\Pipeline\Support\RequirementResolver;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\RuleInferrers\NullableRuleInferrer;
use Spatie\LaravelData\RuleInferrers\RuleInferrer;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Validation\PropertyRules;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\LaravelData\Support\Validation\ValidationPath;

beforeEach(function () {
    $this->resolver = RequirementResolver::fromConfig();
});

function requirementProperty(string $name): DataProperty
{
    return app(DataConfig::class)
        ->getDataClass(RequirementResolverTestData::class)
        ->properties
        ->first(fn($property) => $property->name === $name);
}

dataset('requirement expectations', [
    // [property, required, nullable, onlyValidatedWhenPresent]
    'Required attribute overrides a nullable type'  => ['email', true, true, false],
    'Nullable attribute does not remove required'   => ['middleName', true, true, false],
    'Sometimes attribute makes a property optional' => ['couponCode', false, false, true],
    'Required attribute loses to a default value'   => ['status', false, false, false],
    'nullable type without attribute'               => ['nickname', false, true, false],
    'plain type without attribute'                  => ['title', true, false, false],
    'Optional type without attribute'               => ['note', false, false, true],
]);

it('reconciles declared type with requirement attribute', function (
    string $property,
    bool $required,
    bool $nullable,
    bool $onlyValidatedWhenPresent,
) {
    $status = $this->resolver->resolve(requirementProperty($property));

    expect($status)->not->toBeNull()
        ->and($status->required)->toBe($required)
        ->and($status->nullable)->toBe($nullable)
        ->and($status->onlyValidatedWhenPresent)->toBe($onlyValidatedWhenPresent);
})->with('requirement expectations');

it('runs the configured inferrers without a payload', function () {
    // Guard: fails if any configured inferrer starts reading the ValidationContext.
    $context = new ValidationContext(null, null, ValidationPath::create());
    $property = requirementProperty('email');

    $inferrers = app(DataConfig::class)->ruleInferrers;

    expect($inferrers)->not->toBeEmpty();

    foreach ($inferrers as $inferrer) {
        expect($inferrer->handle($property, new PropertyRules(), $context))
            ->toBeInstanceOf(PropertyRules::class);
    }
});

it('returns null instead of propagating a failure', function () {
    $exploding = new class implements RuleInferrer {
        public function handle(DataProperty $property, PropertyRules $rules, ValidationContext $context): PropertyRules
        {
            throw new RuntimeException('upstream changed');
        }
    };

    $resolver = new RequirementResolver([$exploding]);

    expect($resolver->resolve(requirementProperty('email')))->toBeNull();
});

it('treats an empty inferrer list as unresolvable rather than as "nothing required"', function () {
    $resolver = new RequirementResolver([]);

    expect($resolver->resolve(requirementProperty('title')))->toBeNull();
});

it('honours a customised inferrer list rather than a hardcoded one', function () {
    // Only the nullable inferrer: nothing should come out required.
    $resolver = new RequirementResolver([new NullableRuleInferrer()]);

    $status = $resolver->resolve(requirementProperty('title'));

    expect($status)->not->toBeNull()
        ->and($status->required)->toBeFalse();
});

class RequirementResolverTestData extends Data
{
    public function __construct(
        #[Spatie\LaravelData\Attributes\Validation\Required]
        public ?string $email,
        #[Spatie\LaravelData\Attributes\Validation\Nullable]
        public string $middleName,
        #[Spatie\LaravelData\Attributes\Validation\Sometimes]
        public string $couponCode,
        public ?string $nickname,
        public string $title,
        public string|Optional $note,
        #[Spatie\LaravelData\Attributes\Validation\Required]
        public string $status = 'draft',
    ) {}
}
