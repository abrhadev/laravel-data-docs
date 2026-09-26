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
    // Required and Filled are implicit rules: Laravel runs them against null
    // even when Nullable is present, so null is rejected and not published.
    'Required attribute overrides a nullable type'     => ['email', true, false, false],
    'Nullable attribute does not remove required'      => ['middleName', true, false, false],
    'Filled attribute rejects null on a nullable type' => ['filledNullable', false, false, false],
    'Present attribute still accepts null'             => ['presentNullable', true, true, false],
    'Accepted attribute is required and rejects null'  => ['acceptedNullable', true, false, false],
    'Declined attribute is required and rejects null'  => ['declinedNullable', true, false, false],
    'conditional attribute keeps a nullable type'      => ['conditionalNullable', false, true, false],
    'Sometimes attribute makes a property optional'    => ['couponCode', false, false, true],
    // Upstream removes Sometimes when it adds a requiring rule, and not the other
    // way round, so declaration order decides. Both orders match runtime.
    'Sometimes declared before Required is removed' => ['sometimesThenRequired', true, false, false],
    'Sometimes declared after Required survives'    => ['requiredThenSometimes', false, false, true],
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

function prohibitionProperty(string $name): DataProperty
{
    return app(DataConfig::class)
        ->getDataClass(ProhibitionResolverTestData::class)
        ->properties
        ->first(fn($property) => $property->name === $name);
}

it('publishes a required-by-type prohibited or excluded property as required', function (string $property) {
    // Neither family implements RequiringRule, so the inferred Required stays.
    expect($this->resolver->resolve(prohibitionProperty($property))->required)->toBeTrue();
})->with([
    'Prohibited' => ['locked'],
    'Exclude'    => ['excluded'],
]);

it('flags a required, non-empty, barely prohibited property as never satisfiable', function (string $property) {
    expect($this->resolver->resolve(prohibitionProperty($property))->neverSatisfiable)->toBeTrue();
})->with([
    'prohibited alone' => ['locked'],
    // Prohibited runs before the exclusion, so a sent value is still rejected.
    'exclusion declared after' => ['prohibitedFirst'],
]);

it('does not flag a property some request can satisfy', function (string $property) {
    expect($this->resolver->resolve(prohibitionProperty($property))->neverSatisfiable)->toBeFalse();
})->with([
    'defaulted'             => ['defaulted'],
    'nullable'              => ['nullable'],
    'present, may be empty' => ['presentProhibited'],
    'conditional'           => ['conditional'],
    'excluded'              => ['excluded'],
    'wrapped rule'          => ['wrapped'],
    // Laravel stops validating a field once it is excluded, so Prohibited never runs.
    'excluded first'               => ['excludedFirst'],
    'conditionally excluded first' => ['conditionallyExcludedFirst'],
]);

function conditionalProperty(string $name): DataProperty
{
    return app(DataConfig::class)
        ->getDataClass(ConditionalResolverTestData::class)
        ->properties
        ->first(fn($property) => $property->name === $name);
}

it('knows every attribute Laravel Data marks as a requiring rule', function () {
    // Detector for the carve-out: the resolver recognises Required plus six
    // conditional classes. A new or removed RequiringRule attribute upstream
    // fails here instead of silently changing published requirement status.
    $directory = dirname((new ReflectionClass(Spatie\LaravelData\Attributes\Validation\Required::class))->getFileName());
    $requiring = [];

    foreach (glob($directory . '/*.php') as $file) {
        $class = 'Spatie\\LaravelData\\Attributes\\Validation\\' . basename($file, '.php');

        if (class_exists($class) && is_subclass_of($class, Spatie\LaravelData\Support\Validation\RequiringRule::class)) {
            $requiring[] = $class;
        }
    }

    sort($requiring);

    expect($requiring)->toBe([
        Spatie\LaravelData\Attributes\Validation\Required::class,
        Spatie\LaravelData\Attributes\Validation\RequiredIf::class,
        Spatie\LaravelData\Attributes\Validation\RequiredUnless::class,
        Spatie\LaravelData\Attributes\Validation\RequiredWith::class,
        Spatie\LaravelData\Attributes\Validation\RequiredWithAll::class,
        Spatie\LaravelData\Attributes\Validation\RequiredWithout::class,
        Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll::class,
    ]);
});

it('publishes a conditionally required property as optional', function (string $property) {
    // Each of the six implements the same empty RequiringRule marker as #[Required];
    // the non-nullable case also has an inferred Required that the condition replaces.
    $status = $this->resolver->resolve(conditionalProperty($property));

    expect($status)->not->toBeNull()
        ->and($status->required)->toBeFalse();
})->with([
    'RequiredIf'         => ['conditionalIf'],
    'RequiredUnless'     => ['conditionalUnless'],
    'RequiredWith'       => ['conditionalWith'],
    'RequiredWithAll'    => ['conditionalWithAll'],
    'RequiredWithout'    => ['conditionalWithout'],
    'RequiredWithoutAll' => ['conditionalWithoutAll'],
    'non-nullable type'  => ['conditionalPlain'],
]);

it('still publishes an unconditionally required property as required', function () {
    $status = $this->resolver->resolve(requirementProperty('email'));

    expect($status->required)->toBeTrue();
});

it('publishes a Present property as required', function () {
    // Upstream strips every requiring rule when it sees Present, so this is not
    // inherited from the inferrers; the resolver reads Present directly.
    $status = $this->resolver->resolve(conditionalProperty('present'));

    expect($status->required)->toBeTrue();
});

it('publishes a defaulted Present property as optional', function () {
    $status = $this->resolver->resolve(conditionalProperty('presentWithDefault'));

    expect($status->required)->toBeFalse();
});

it('publishes a defaulted conditional property as optional', function () {
    $status = $this->resolver->resolve(conditionalProperty('conditionalWithDefault'));

    expect($status->required)->toBeFalse();
});

it('loses the sometimes flag when a conditional rule is present', function () {
    // AttributesRuleInferrer removes Sometimes whenever it adds a requiring rule,
    // so an Optional-typed property carrying one is not "only validated when present".
    $status = $this->resolver->resolve(conditionalProperty('conditionalOptional'));

    expect($status->onlyValidatedWhenPresent)->toBeFalse();
});

it('treats a subclass of a conditional requiring rule as conditional, as Laravel does', function () {
    $status = $this->resolver->resolve(conditionalProperty('customRequiring'));
    $rules = ConditionalResolverTestData::getValidationRules([])['customRequiring'];

    expect($status->required)->toBeFalse()
        ->and($status->nullable)->toBeTrue()
        ->and(Illuminate\Support\Facades\Validator::make([], ['customRequiring' => $rules])->passes())->toBeTrue();
});

it('treats a conditional subclass that emits a rule of its own as unconditional, as Laravel does', function () {
    $status = $this->resolver->resolve(conditionalProperty('alwaysRequired'));
    $rules = ConditionalResolverTestData::getValidationRules([])['alwaysRequired'];

    expect($rules)->toContain('required')
        ->and($status->required)->toBeTrue()
        ->and($status->nullable)->toBeFalse()
        ->and(Illuminate\Support\Facades\Validator::make(['accountType' => 'personal'], ['alwaysRequired' => $rules])->fails())->toBeTrue();
});

it('keeps a conditional subclass conditional while it emits a conditional keyword, as Laravel does', function (string $property) {
    $status = $this->resolver->resolve(conditionalProperty($property));
    $rules = ConditionalResolverTestData::getValidationRules([])[$property];

    // The condition does not hold for a personal account, so the field may be omitted.
    expect($status->required)->toBeFalse()
        ->and(Illuminate\Support\Facades\Validator::make(['accountType' => 'personal'], [$property => $rules])->passes())->toBeTrue();
})->with(['parametersOnly', 'restatedKeyword', 'otherConditional']);

it('reads a conditional subclass that switches to another conditional implicit keyword as conditional, as Laravel does', function () {
    $status = $this->resolver->resolve(conditionalProperty('ifAccepted'));
    $rules = ConditionalResolverTestData::getValidationRules([])['ifAccepted'];

    expect($rules)->toContain('required_if_accepted:accountType')
        ->and($status->required)->toBeFalse()
        ->and($status->nullable)->toBeTrue()
        ->and(Illuminate\Support\Facades\Validator::make(['accountType' => 'no'], ['ifAccepted' => $rules])->passes())->toBeTrue()
        ->and(Illuminate\Support\Facades\Validator::make(['accountType' => 'no', 'ifAccepted' => null], ['ifAccepted' => $rules])->passes())->toBeTrue()
        ->and(Illuminate\Support\Facades\Validator::make(['accountType' => 'yes'], ['ifAccepted' => $rules])->fails())->toBeTrue();
});

it('lists exactly Laravel\'s implicit rules by keyword', function () {
    $keywords = (new ReflectionClassConstant(RequirementResolver::class, 'IMPLICIT_KEYWORDS'))->getValue();
    $implicit = (new ReflectionClass(Illuminate\Validation\Validator::class))->getDefaultProperties()['implicitRules'];

    expect(array_map(fn(string $keyword) => Illuminate\Support\Str::studly($keyword), array_keys($keywords)))
        ->toEqualCanonicalizing($implicit);
});

class ConditionalResolverTestData extends Data
{
    public function __construct(
        #[Spatie\LaravelData\Attributes\Validation\RequiredIf('accountType', 'business')]
        public ?string $conditionalIf,
        #[Spatie\LaravelData\Attributes\Validation\RequiredUnless('status', 'approved')]
        public ?string $conditionalUnless,
        #[Spatie\LaravelData\Attributes\Validation\RequiredWith('cardNumber')]
        public ?string $conditionalWith,
        #[Spatie\LaravelData\Attributes\Validation\RequiredWithAll(['a', 'b'])]
        public ?string $conditionalWithAll,
        #[Spatie\LaravelData\Attributes\Validation\RequiredWithout('email')]
        public ?string $conditionalWithout,
        #[Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll(['email', 'phone'])]
        public ?string $conditionalWithoutAll,
        #[Spatie\LaravelData\Attributes\Validation\Present]
        public string $present,
        #[Spatie\LaravelData\Attributes\Validation\RequiredIf('accountType', 'business')]
        public string|Optional $conditionalOptional,
        #[SubclassedRequiredIf('accountType', 'business')]
        public ?string $customRequiring,
        #[AlwaysRequiredIf('accountType', 'business')]
        public ?string $alwaysRequired,
        #[ParametersOnlyRequiredIf('accountType', 'business')]
        public ?string $parametersOnly,
        #[RestatedKeywordRequiredIf('accountType', 'business')]
        public ?string $restatedKeyword,
        #[UnlessKeywordRequiredIf('accountType', 'personal')]
        public ?string $otherConditional,
        #[IfAcceptedKeywordRequiredIf('accountType')]
        public ?string $ifAccepted,
        #[Spatie\LaravelData\Attributes\Validation\RequiredIf('accountType', 'business')]
        public string $conditionalPlain,
        #[Spatie\LaravelData\Attributes\Validation\Present]
        public string $presentWithDefault = 'x',
        #[Spatie\LaravelData\Attributes\Validation\RequiredIf('accountType', 'business')]
        public string $conditionalWithDefault = 'draft',
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class SubclassedRequiredIf extends Spatie\LaravelData\Attributes\Validation\RequiredIf {}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ParametersOnlyRequiredIf extends Spatie\LaravelData\Attributes\Validation\RequiredIf
{
    public function parameters(): array
    {
        return parent::parameters();
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class RestatedKeywordRequiredIf extends Spatie\LaravelData\Attributes\Validation\RequiredIf
{
    public static function keyword(): string
    {
        return 'required_if';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class UnlessKeywordRequiredIf extends Spatie\LaravelData\Attributes\Validation\RequiredIf
{
    public static function keyword(): string
    {
        return 'required_unless';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class IfAcceptedKeywordRequiredIf extends Spatie\LaravelData\Attributes\Validation\RequiredIf
{
    public static function keyword(): string
    {
        return 'required_if_accepted';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class AlwaysRequiredIf extends Spatie\LaravelData\Attributes\Validation\RequiredIf
{
    public static function keyword(): string
    {
        return 'required';
    }

    public function parameters(): array
    {
        return [];
    }
}

class RequirementResolverTestData extends Data
{
    public function __construct(
        #[Spatie\LaravelData\Attributes\Validation\Accepted]
        public ?bool $acceptedNullable,
        #[Spatie\LaravelData\Attributes\Validation\Declined]
        public ?bool $declinedNullable,
        #[Spatie\LaravelData\Attributes\Validation\Required]
        public ?string $email,
        #[Spatie\LaravelData\Attributes\Validation\Nullable]
        public string $middleName,
        #[Spatie\LaravelData\Attributes\Validation\Sometimes]
        public string $couponCode,
        public ?string $nickname,
        public string $title,
        public string|Optional $note,
        #[Spatie\LaravelData\Attributes\Validation\Filled]
        public ?string $filledNullable,
        #[Spatie\LaravelData\Attributes\Validation\Present]
        public ?string $presentNullable,
        #[Spatie\LaravelData\Attributes\Validation\RequiredIf('account_type', 'business')]
        public ?string $conditionalNullable,
        #[Spatie\LaravelData\Attributes\Validation\Sometimes]
        #[Spatie\LaravelData\Attributes\Validation\Required]
        public string $sometimesThenRequired,
        #[Spatie\LaravelData\Attributes\Validation\Required]
        #[Spatie\LaravelData\Attributes\Validation\Sometimes]
        public string $requiredThenSometimes,
        #[Spatie\LaravelData\Attributes\Validation\Required]
        public string $status = 'draft',
    ) {}
}

class ProhibitionResolverTestData extends Data
{
    public function __construct(
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        public string $locked,
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        public ?string $nullable,
        #[Spatie\LaravelData\Attributes\Validation\Present]
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        public ?string $presentProhibited,
        #[Spatie\LaravelData\Attributes\Validation\ProhibitedIf('plan', 'enterprise')]
        public string $conditional,
        #[Spatie\LaravelData\Attributes\Validation\Exclude]
        public string $excluded,
        #[Spatie\LaravelData\Attributes\Validation\Prohibited(new Illuminate\Validation\Rules\ProhibitedIf(true))]
        public string $wrapped,
        #[Spatie\LaravelData\Attributes\Validation\Exclude]
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        public string $excludedFirst,
        #[Spatie\LaravelData\Attributes\Validation\ExcludeIf('mode', 'legacy')]
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        public string $conditionallyExcludedFirst,
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        #[Spatie\LaravelData\Attributes\Validation\Exclude]
        public string $prohibitedFirst,
        #[Spatie\LaravelData\Attributes\Validation\Prohibited]
        public string $defaulted = 'x',
    ) {}
}

it('treats a rule implementing RequiringRule directly as unconditional', function () {
    $property = app(DataConfig::class)
        ->getDataClass(DirectRequiringResolverTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'code');
    $status = $this->resolver->resolve($property);
    $rules = DirectRequiringResolverTestData::getValidationRules([]);

    expect($status)->not->toBeNull()
        ->and($status->required)->toBeTrue()
        ->and(Illuminate\Support\Facades\Validator::make([], $rules)->fails())->toBeTrue()
        ->and(Illuminate\Support\Facades\Validator::make(['code' => 'a'], $rules)->passes())->toBeTrue();
});

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class DirectRequiringRule extends Spatie\LaravelData\Attributes\Validation\StringValidationAttribute implements Spatie\LaravelData\Support\Validation\RequiringRule
{
    public static function keyword(): string
    {
        return 'required';
    }

    public function parameters(): array
    {
        return [];
    }

    public static function create(string ...$parameters): static
    {
        return new static();
    }
}

class DirectRequiringResolverTestData extends Data
{
    public function __construct(
        #[DirectRequiringRule]
        public ?string $code,
    ) {}
}
