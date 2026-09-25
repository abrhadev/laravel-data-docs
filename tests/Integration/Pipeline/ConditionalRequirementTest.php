<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Filled;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\RequiredWithAll;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataConfig;

function conditionalThroughPipeline(string $property, string $class = ConditionalTestData::class): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass($class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

it('AC1: publishes a value-conditional property as optional and states the condition', function () {
    $context = conditionalThroughPipeline('companyName');

    expect($context->required)->toBeFalse()
        ->and($context->description)->toContain('Required when <b><i>account_type</i></b> is <code>business</code>.');
});

it('AC2: states an inverted value condition', function () {
    $context = conditionalThroughPipeline('reason');

    expect($context->required)->toBeFalse()
        ->and($context->description)->toContain('Required unless <b><i>status</i></b> is <code>approved</code>.');
});

it('AC3: states a condition on the presence of another field', function () {
    $cardCvc = conditionalThroughPipeline('cardCvc');
    $shippingCity = conditionalThroughPipeline('shippingCity');

    expect($cardCvc->required)->toBeFalse()
        ->and($cardCvc->description)->toContain('Required when <b><i>card_number</i></b> is present.')
        ->and($shippingCity->required)->toBeFalse()
        ->and($shippingCity->description)->toContain('Required when both <b><i>shipping_street</i></b> and <b><i>shipping_country</i></b> are present.');
});

it('AC4: states a condition on the absence of another field', function () {
    $phone = conditionalThroughPipeline('phone');
    $fallback = conditionalThroughPipeline('fallbackContact');

    expect($phone->required)->toBeFalse()
        ->and($phone->description)->toContain('Required when <b><i>email</i></b> is not present.')
        ->and($fallback->required)->toBeFalse()
        ->and($fallback->description)->toContain('Required when neither <b><i>email</i></b> nor <b><i>phone</i></b> is present.');
});

it('AC5: distinguishes presence-only from value requirements', function () {
    // AC5 originally expected the inclusion sentence on #[Present] string. With
    // Laravel's default ConvertEmptyStringsToNull an empty value becomes null and
    // fails the string rule there, so only the nullable form states it.
    $terms = conditionalThroughPipeline('terms');
    $optionalTerms = conditionalThroughPipeline('nullableTerms');
    $title = conditionalThroughPipeline('title');

    expect($terms->required)->toBeTrue()
        ->and($terms->description)->not->toContain('but may be empty')
        ->and($optionalTerms->required)->toBeTrue()
        ->and($optionalTerms->description)->toContain('Must be included in the request, but may be empty.')
        ->and($title->required)->toBeTrue()
        ->and($title->description)->toContain('When included, must not be empty.');
});

it('AC6: produces one coherent description in a stable order', function () {
    $context = conditionalThroughPipeline('vatNumber');

    expect($context->description)->toBe(
        'Must be a string. Required when <b><i>account_type</i></b> is <code>business</code>. A null value is accepted.'
    )
        ->and($context->required)->toBeFalse()
        ->and($context->nullable)->toBeTrue();
});

it('AC6: repeats byte-identically across successive builds', function () {
    expect(conditionalThroughPipeline('vatNumber')->description)
        ->toBe(conditionalThroughPipeline('vatNumber')->description);
});

it('AC7: a dangling field reference does not break the build', function () {
    $dangling = conditionalThroughPipeline('dangling', DanglingReferenceTestData::class);
    $sibling = conditionalThroughPipeline('sibling', DanglingReferenceTestData::class);

    expect($dangling->required)->toBeFalse()
        ->and($dangling->description)->toContain('Required when <b><i>does_not_exist</i></b> is <code>whatever</code>.')
        ->and($sibling->description)->toBe('Must be a string.')
        ->and($sibling->required)->toBeTrue();
});

it('suppresses a condition sentence the API does not enforce', function () {
    // Present strips every requiring rule declared before it, so the conditional
    // requirement is not in force and must not be published.
    $context = conditionalThroughPipeline('conditionalThenPresent');

    expect(ConditionalTestData::getValidationRules([])['conditionalThenPresent'])
        ->not->toContain('required_if:account_type,business')
        ->and($context->description)->not->toContain('Required when');
});

it('keeps a condition sentence declared after Present, which the API still enforces', function () {
    $context = conditionalThroughPipeline('presentThenConditional');

    expect(ConditionalTestData::getValidationRules([])['presentThenConditional'])
        ->toContain('required_if:account_type,business')
        ->and($context->description)->toContain('Required when <b><i>account_type</i></b> is <code>business</code>.');
});

it('suppresses a condition replaced by a later requiring attribute', function () {
    // Adding a requiring rule upstream replaces every requiring rule already
    // collected, so only the last conditional attribute is enforced.
    $context = conditionalThroughPipeline('ifThenWith');
    $rules = ConditionalTestData::getValidationRules([])['ifThenWith'];

    expect($rules)->toContain('required_with:card_number')
        ->and($rules)->not->toContain('required_if:account_type,business')
        ->and($context->description)->not->toContain('Required when <b><i>account_type</i></b>')
        ->and($context->description)->toContain('Required when <b><i>card_number</i></b> is present.');
});

it('suppresses a condition replaced by a later Required', function () {
    $context = conditionalThroughPipeline('ifThenRequired');
    $rules = ConditionalTestData::getValidationRules([])['ifThenRequired'];

    expect($rules)->toContain('required')
        ->and($rules)->not->toContain('required_if:account_type,business')
        ->and($context->required)->toBeTrue()
        ->and($context->description)->not->toContain('Required when');
});

it('does not claim null is accepted where Filled rejects it', function () {
    $context = conditionalThroughPipeline('filledNullable');

    expect(fn() => FilledNullableTestData::validate(['filledNullable' => null]))
        ->toThrow(Illuminate\Validation\ValidationException::class)
        ->and($context->nullable)->toBeFalse()
        ->and($context->description)->toContain('When included, must not be empty.')
        ->and($context->description)->not->toContain('A null value is accepted.');
});

it('states Present only where the key cannot be omitted', function (string $property, bool $stated) {
    $context = conditionalThroughPipeline($property, PresentOmissionTestData::class);
    $rules = PresentOmissionTestData::getValidationRules([]);
    $omittable = !array_key_exists($property, $rules)
        || validator([], [$property => $rules[$property]])->passes();

    expect($omittable)->toBe(!$stated)
        ->and($context->required)->toBe($stated)
        ->and(str_contains($context->description, 'Must be included in the request, but may be empty.'))->toBe($stated);
})->with([
    'plain type'      => ['plain', true],
    'Optional type'   => ['optional', false],
    'Sometimes first' => ['sometimesFirst', false],
    'Sometimes after' => ['sometimesAfter', false],
    'default value'   => ['defaulted', false],
]);

it('says Present may be empty only where an empty value survives end to end', function (string $property) {
    // An empty value must pass both as sent and as Laravel's default
    // ConvertEmptyStringsToNull delivers it (null), through validation and Data
    // construction, not only through the validator.
    $context = conditionalThroughPipeline($property, PresentEmptinessTestData::class);
    $empty = $context->type === 'object' ? [] : '';
    $survives = function (mixed $value) use ($property): bool {
        try {
            PresentEmptinessTestData::validateAndCreate([...presentEmptinessPayload(), $property => $value]);

            return true;
        } catch (Throwable) {
            return false;
        }
    };

    expect($context->required)->toBeTrue()
        ->and(str_contains($context->description, 'but may be empty'))->toBe($survives($empty) && $survives(null));
})->with(fn() => array_keys(presentEmptinessPayload()));

function presentEmptinessPayload(): array
{
    return [
        'plain'               => 'a',
        'nullable'            => 'a',
        'nullableRuleOnly'    => 'a',
        'number'              => 1,
        'decimal'             => 1.5,
        'status'              => 'active',
        'date'                => '2026-01-01T00:00:00+00:00',
        'withNonImplicitRule' => 'abcd',
        'presentThenRequired' => 'a',
        'requiredThenPresent' => 'a',
        'withFilled'          => 'a',
        'withAccepted'        => true,
        'nested'              => ['street' => 'Main'],
    ];
}

class PresentEmptinessNestedData extends Data
{
    public function __construct(
        public string $street,
    ) {}
}

enum PresentEmptinessStatus: string
{
    case Active = 'active';
}

class PresentEmptinessTestData extends Data
{
    public function __construct(
        #[Present]
        public string $plain,
        #[Present]
        public ?string $nullable,
        #[Present, Nullable]
        public string $nullableRuleOnly,
        #[Present]
        public ?int $number,
        #[Present]
        public ?float $decimal,
        #[Present]
        public ?PresentEmptinessStatus $status,
        #[Present]
        public ?Carbon\CarbonImmutable $date,
        #[Present, Min(3)]
        public ?string $withNonImplicitRule,
        #[Present, Required]
        public ?string $presentThenRequired,
        #[Required, Present]
        public ?string $requiredThenPresent,
        #[Present, Filled]
        public string $withFilled,
        #[Present, Accepted]
        public bool $withAccepted,
        #[Present]
        public PresentEmptinessNestedData $nested,
    ) {}
}

class PresentOmissionTestData extends Data
{
    public function __construct(
        #[Present]
        public ?string $plain,
        #[Present]
        public string|Optional|null $optional,
        #[Sometimes, Present]
        public ?string $sometimesFirst,
        #[Present, Sometimes]
        public ?string $sometimesAfter,
        #[Present]
        public ?string $defaulted = 'x',
    ) {}
}

it('treats a requiring rule declared through #[Rule] like the attribute', function (string $property, bool $conditionEnforced) {
    // Upstream expands #[Rule] strings into rule objects before adding them, so
    // a later requiring rule replaces the condition. #[Rule('present')] does not
    // strip it: upstream checks the declared attribute for Present, not its rules.
    $context = conditionalThroughPipeline($property, RuleStringTestData::class);
    $rules = RuleStringTestData::getValidationRules([])[$property];

    expect(in_array('required_if:account_type,business', $rules, true))->toBe($conditionEnforced)
        ->and(str_contains($context->description, 'Required when <b><i>account_type</i></b>'))->toBe($conditionEnforced);
})->with([
    'later Rule required'      => ['thenRuleRequired', false],
    'later Rule required_with' => ['thenRuleRequiredWith', false],
    'later Rule present'       => ['thenRulePresent', true],
    'earlier Rule required'    => ['ruleRequiredFirst', true],
]);

class RuleStringTestData extends Data
{
    public function __construct(
        #[RequiredIf('account_type', 'business')]
        #[Rule('required')]
        public ?string $thenRuleRequired,
        #[RequiredIf('account_type', 'business')]
        #[Rule('required_with:card_number')]
        public ?string $thenRuleRequiredWith,
        #[RequiredIf('account_type', 'business')]
        #[Rule('present')]
        public ?string $thenRulePresent,
        #[Rule('required')]
        #[RequiredIf('account_type', 'business')]
        public ?string $ruleRequiredFirst,
    ) {}
}

class FilledNullableTestData extends Data
{
    public function __construct(
        #[Filled]
        public ?string $filledNullable,
    ) {}
}

class ConditionalTestData extends Data
{
    public function __construct(
        #[RequiredIf('account_type', 'business')]
        public ?string $companyName,
        #[RequiredUnless('status', 'approved')]
        public ?string $reason,
        #[RequiredWith('card_number')]
        public ?string $cardCvc,
        #[RequiredWithAll(['shipping_street', 'shipping_country'])]
        public ?string $shippingCity,
        #[RequiredWithout('email')]
        public ?string $phone,
        #[RequiredWithoutAll(['email', 'phone'])]
        public ?string $fallbackContact,
        #[Present]
        public string $terms,
        #[Present]
        public ?string $nullableTerms,
        #[Filled]
        public string $title,
        #[RequiredIf('account_type', 'business')]
        #[Nullable]
        public ?string $vatNumber,
        #[RequiredIf('account_type', 'business')]
        #[Present]
        public string $conditionalThenPresent,
        #[Present]
        #[RequiredIf('account_type', 'business')]
        public string $presentThenConditional,
        #[RequiredIf('account_type', 'business')]
        #[RequiredWith('card_number')]
        public ?string $ifThenWith,
        #[RequiredIf('account_type', 'business')]
        #[Required]
        public ?string $ifThenRequired,
        #[Filled]
        public ?string $filledNullable,
    ) {}
}

class DanglingReferenceTestData extends Data
{
    public function __construct(
        #[RequiredIf('does_not_exist', 'whatever')]
        public ?string $dangling,
        public string $sibling,
    ) {}
}
