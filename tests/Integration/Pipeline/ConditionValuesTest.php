<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\AcceptedIf;
use Spatie\LaravelData\Attributes\Validation\DeclinedIf;
use Spatie\LaravelData\Attributes\Validation\Different;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\InArray;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Attributes\Validation\ExcludeIf;
use Spatie\LaravelData\Attributes\Validation\ExcludeUnless;
use Spatie\LaravelData\Attributes\Validation\ExcludeWith;
use Spatie\LaravelData\Attributes\Validation\ExcludeWithout;
use Spatie\LaravelData\Attributes\Validation\Prohibits;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Attributes\Validation\ProhibitedUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function conditionValuesThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ConditionValuesTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(new ParameterContext($dataProperty->name, $dataProperty));
}

function conditionValuesFail(string $property, array $payload): bool
{
    $rules = ConditionValuesTestData::getValidationRules($payload);

    return Validator::make($payload, [$property => $rules[$property], 'country' => $rules['country']])->fails();
}

it('states a comma-joined condition value as the values Laravel compares against', function (string $property, string $phrase, array $triggering) {
    expect(conditionValuesThroughPipeline($property)->description)->toContain($phrase)
        ->and(conditionValuesFail($property, $triggering))->toBeTrue()
        ->and(conditionValuesFail($property, ['country' => 'DE,AT', $property => 'v']))->toBeFalse();
})->with([
    'accepted if'   => ['accepted', '<b><i>country</i></b> is one of: <code>DE</code>, <code>AT</code>', ['country' => 'AT', 'accepted' => 'no']],
    'required if'   => ['required', '<b><i>country</i></b> is one of: <code>DE</code>, <code>AT</code>', ['country' => 'AT']],
    'prohibited if' => ['prohibited', '<b><i>country</i></b> is one of: <code>DE</code>, <code>AT</code>', ['country' => 'AT', 'prohibited' => 'v']],
]);

it('states an exclusion condition with a comma-joined value as its values', function () {
    $rules = ConditionValuesTestData::getValidationRules(['country' => 'AT', 'excluded' => 'v']);
    $validated = Validator::make(['country' => 'AT', 'excluded' => 'v'], ['excluded' => $rules['excluded'], 'country' => $rules['country']])->validated();

    expect($validated)->not->toHaveKey('excluded')
        ->and(conditionValuesThroughPipeline('excluded')->description)->toContain('<b><i>country</i></b> is one of: <code>DE</code>, <code>AT</code>');
});

it('states the value an accepted_if rule string is enforced with', function () {
    // AcceptedIf::create() maps '1' to the string 'true', which is what runs.
    expect(ConditionValuesTestData::getValidationRules([])['from_rule'])->toContain('accepted_if:country,true')
        ->and(conditionValuesThroughPipeline('from_rule')->description)->toContain('<b><i>country</i></b> is <code>true</code>');
});

it('states a comma-joined unless value as the values Laravel compares against', function () {
    $rules = ConditionValuesTestData::getValidationRules([]);

    expect(conditionValuesThroughPipeline('required_unless')->description)->toContain('unless <b><i>country</i></b> is one of: <code>DE</code>, <code>AT</code>')
        ->and(Validator::make(['country' => 'FR'], ['required_unless' => $rules['required_unless']])->fails())->toBeTrue()
        ->and(Validator::make(['country' => 'AT'], ['required_unless' => $rules['required_unless']])->fails())->toBeFalse()
        ->and(conditionValuesThroughPipeline('excluded_unless')->description)->toContain('unless <b><i>country</i></b> is one of: <code>DE</code>, <code>AT</code>')
        ->and(Validator::make(['country' => 'AT', 'excluded_unless' => 'v'], ['excluded_unless' => $rules['excluded_unless']])->validated())->toHaveKey('excluded_unless')
        ->and(Validator::make(['country' => 'FR', 'excluded_unless' => 'v'], ['excluded_unless' => $rules['excluded_unless']])->validated())->not->toHaveKey('excluded_unless');
});

function conditionFieldsValidator(string $property, array $payload): Illuminate\Validation\Validator
{
    $rules = ConditionValuesTestData::getValidationRules($payload);

    return Validator::make($payload, array_intersect_key($rules, array_flip([$property, 'first', 'second'])));
}

it('states a comma-joined field reference as the fields Laravel reads', function () {
    expect(conditionValuesThroughPipeline('required_with')->description)->toContain('Required when any of <b><i>first</i></b>, <b><i>second</i></b> is present.')
        ->and(conditionFieldsValidator('required_with', ['second' => 'v'])->errors()->has('required_with'))->toBeTrue()
        ->and(conditionValuesThroughPipeline('prohibits')->description)->toContain('forbids sending <b><i>first</i></b> and <b><i>second</i></b>; the request is rejected if any of them is also sent.')
        ->and(conditionFieldsValidator('prohibits', ['prohibits' => 'v', 'second' => 'v'])->errors()->has('prohibits'))->toBeTrue();
});

it('states a comma-joined exclusion reference as the fields Laravel reads', function (string $property, string $sentence, array $excluding, array $keeping) {
    expect(conditionValuesThroughPipeline($property)->description)->toContain($sentence)
        ->and(conditionFieldsValidator($property, [...$excluding, $property => 'v'])->validated())->not->toHaveKey($property)
        ->and(conditionFieldsValidator($property, [...$keeping, $property => 'v'])->validated())->toHaveKey($property);
})->with([
    // exclude_with reads its first field only; exclude_without checks every one.
    'with'    => ['excluded_with', 'when <b><i>first</i></b> is present.', ['first' => 'v'], ['second' => 'v']],
    'without' => ['excluded_without', 'when any of <b><i>first</i></b>, <b><i>second</i></b> is not present.', ['first' => 'v'], ['first' => 'v', 'second' => 'v']],
    // exclude_if written 'a,b' reads the field a, and b becomes a compared value.
    'if' => ['excluded_ref', 'removed from the validated input when <b><i>first</i></b> is one of: <code>second</code>, <code>x</code>.', ['first' => 'second'], ['first' => 'other']],
]);

it('states a null compared value as the empty string Laravel compares against', function () {
    // Spatie writes required_unless:country, so only an empty country exempts the field.
    $rules = ConditionValuesTestData::getValidationRules([])['unless_null'];

    expect(conditionValuesThroughPipeline('unless_null')->description)->toContain('Required unless <b><i>country</i></b> is <code>""</code>.')
        ->and(Validator::make([], ['unless_null' => $rules])->fails())->toBeTrue()
        ->and(Validator::make(['country' => null], ['unless_null' => $rules])->fails())->toBeTrue()
        ->and(Validator::make(['country' => ''], ['unless_null' => $rules])->fails())->toBeFalse();
});

it('reads a comma-joined cross-field or comparison reference as Laravel does', function (string $property, string $sentence, array $failing, array $passing) {
    expect(conditionValuesThroughPipeline($property)->description)->toContain($sentence)
        ->and(conditionFieldsValidator($property, $failing)->errors()->has($property))->toBeTrue()
        ->and(conditionFieldsValidator($property, $passing)->errors()->has($property))->toBeFalse();
})->with([
    // same, in_array and a comparison read the first field; different checks every one.
    'same'      => ['same_ref', 'Must match the value of <b><i>first</i></b>.', ['first' => 'x', 'second' => 'y', 'same_ref' => 'y'], ['first' => 'x', 'second' => 'y', 'same_ref' => 'x']],
    'in_array'  => ['in_array_ref', 'Must equal the value of <b><i>first</i></b>.', ['first' => 'x', 'second' => 'y', 'in_array_ref' => 'y'], ['first' => 'x', 'second' => 'y', 'in_array_ref' => 'x']],
    'greater'   => ['greater_ref', 'Must be greater than <b><i>first</i></b>.', ['first' => '5', 'second' => '1', 'greater_ref' => 4], ['first' => '5', 'second' => '9', 'greater_ref' => 6]],
    'different' => ['different_ref', 'Must differ from the value of each of <b><i>first</i></b>, <b><i>second</i></b>.', ['first' => 'x', 'second' => 'y', 'different_ref' => 'y'], ['first' => 'x', 'second' => 'y', 'different_ref' => 'z']],
    // A condition's field written 'a,b' reads the field a, and b becomes a compared value.
    'accepted if'     => ['accepted_ref', 'Must be accepted when <b><i>first</i></b> is one of: <code>second</code>, <code>x</code>', ['first' => 'second', 'accepted_ref' => 'no'], ['first' => 'other', 'accepted_ref' => 'no']],
    'required if'     => ['required_ref', 'Required when <b><i>first</i></b> is one of: <code>second</code>, <code>x</code>.', ['first' => 'x'], ['first' => 'other']],
    'declined if'     => ['declined_ref', 'Must be declined when <b><i>first</i></b> is one of: <code>second</code>, <code>x</code>', ['first' => 'x', 'declined_ref' => 'yes'], ['first' => 'other', 'declined_ref' => 'yes']],
    'prohibited if'   => ['prohibited_ref', 'Must not be sent when <b><i>first</i></b> is one of: <code>second</code>, <code>x</code>;', ['first' => 'second', 'prohibited_ref' => 'v'], ['first' => 'other', 'prohibited_ref' => 'v']],
    'required unless' => ['required_unless_ref', 'Required unless <b><i>first</i></b> is one of: <code>second</code>, <code>x</code>.', ['first' => 'other'], ['first' => 'second']],
]);

it('reads a comma-joined condition field with no compared value as the field and its value, as Laravel does', function (string $property, string $sentence, array $failing, array $passing) {
    expect(conditionValuesThroughPipeline($property)->description)->toContain($sentence)
        ->and(conditionFieldsValidator($property, $failing)->errors()->has($property))->toBeTrue()
        ->and(conditionFieldsValidator($property, $passing)->errors()->has($property))->toBeFalse();
})->with([
    // Laravel reads required_if:first,second as "first is second".
    'required if'       => ['required_if_bare', 'Required when <b><i>first</i></b> is <code>second</code>.', ['first' => 'second'], ['first' => 'other']],
    'required unless'   => ['required_unless_bare', 'Required unless <b><i>first</i></b> is <code>second</code>.', ['first' => 'other'], ['first' => 'second']],
    'prohibited if'     => ['prohibited_if_bare', 'Must not be sent when <b><i>first</i></b> is <code>second</code>;', ['first' => 'second', 'prohibited_if_bare' => 'v'], ['first' => 'other', 'prohibited_if_bare' => 'v']],
    'prohibited unless' => ['prohibited_unless_bare', 'Must not be sent unless <b><i>first</i></b> is <code>second</code>;', ['first' => 'other', 'prohibited_unless_bare' => 'v'], ['first' => 'second', 'prohibited_unless_bare' => 'v']],
]);

class ConditionValuesTestData extends Data
{
    public function __construct(
        public ?string $country,
        #[AcceptedIf('country', 'DE,AT')]
        public ?string $accepted,
        #[RequiredIf('country', 'DE,AT')]
        public ?string $required,
        #[ProhibitedIf('country', 'DE,AT')]
        public ?string $prohibited,
        #[ExcludeIf('country', 'DE,AT')]
        public ?string $excluded,
        #[Rule('accepted_if:country,1')]
        public ?string $from_rule,
        #[RequiredUnless('country', 'DE,AT')]
        public ?string $required_unless,
        #[ExcludeUnless('country', 'DE,AT')]
        public ?string $excluded_unless,
        public ?string $first,
        public ?string $second,
        #[RequiredWith('first,second')]
        public ?string $required_with,
        #[Prohibits('first,second')]
        public ?string $prohibits,
        #[ExcludeWith('first,second')]
        public ?string $excluded_with,
        #[ExcludeWithout('first,second')]
        public ?string $excluded_without,
        #[Same('first,second')]
        public ?string $same_ref,
        #[InArray('first,second')]
        public ?string $in_array_ref,
        #[GreaterThan('first,second')]
        public ?int $greater_ref,
        #[Different('first,second')]
        public ?string $different_ref,
        #[AcceptedIf('first,second', 'x')]
        public ?string $accepted_ref,
        #[RequiredIf('first,second', 'x')]
        public ?string $required_ref,
        #[RequiredUnless('country', null)]
        public ?string $unless_null,
        #[DeclinedIf('first,second', 'x')]
        public ?string $declined_ref,
        #[ProhibitedIf('first,second', 'x')]
        public ?string $prohibited_ref,
        #[ExcludeIf('first,second', 'x')]
        public ?string $excluded_ref,
        #[RequiredUnless('first,second', 'x')]
        public ?string $required_unless_ref,
        #[RequiredIf('first,second')]
        public ?string $required_if_bare,
        #[RequiredUnless('first,second')]
        public ?string $required_unless_bare,
        #[ProhibitedIf('first,second')]
        public ?string $prohibited_if_bare,
        #[ProhibitedUnless('first,second')]
        public ?string $prohibited_unless_bare,
    ) {}
}
