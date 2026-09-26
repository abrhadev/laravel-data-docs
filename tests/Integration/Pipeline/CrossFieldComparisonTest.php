<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\AcceptanceProcessor;
use Abrha\LaravelDataDocs\Attributes\Example;
use Abrha\LaravelDataDocs\Attributes\Hidden;
use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\AcceptedIf;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\DeclinedIf;
use Spatie\LaravelData\Attributes\Validation\Different;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\InArray;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function crossFieldRaw(string $class): array
{
    return (new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class)))($class);
}

function crossFieldParameters(string $class): array
{
    return array_map(fn($parameter) => $parameter->toArray(), crossFieldRaw($class));
}

function crossFieldKeyAfter(array $parameters, string $key): ?string
{
    $keys = array_keys($parameters);
    $position = array_search($key, $keys, true);

    return $position === false ? null : ($keys[$position + 1] ?? null);
}

function acceptedList(): string
{
    return '<code>yes</code>, <code>on</code>, <code>1</code>, <code>"1"</code>, <code>true</code>, or <code>"true"</code>';
}

function declinedList(): string
{
    return '<code>no</code>, <code>off</code>, <code>0</code>, <code>"0"</code>, <code>false</code>, or <code>"false"</code>';
}

it('AC1: states that the value must match another field', function () {
    $parameters = crossFieldParameters(CrossFieldComparisonTestData::class);

    expect($parameters['email_confirmation']['description'])->toContain('Must match the value of <b><i>email</i></b>.');
});

it('AC2: states that the value must differ from another field', function () {
    $parameters = crossFieldParameters(CrossFieldComparisonTestData::class);

    expect($parameters['new_password']['description'])->toContain('Must differ from the value of <b><i>current_password</i></b>.');
});

it('AC3: states membership for a wildcard reference and equality for a bare one', function () {
    $parameters = crossFieldParameters(CrossFieldComparisonTestData::class);

    expect($parameters['primary_tag']['description'])->toContain('Must be one of the values submitted in <b><i>tags</i></b>.')
        ->and($parameters['legacy_tag']['description'])->toContain('Must equal the value of <b><i>tags</i></b>.')
        ->and($parameters['legacy_tag']['description'])->not->toContain('submitted in');
});

it('AC4: publishes the confirmation companion directly after its source', function () {
    $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);
    $raw = crossFieldRaw(CrossFieldConfirmationTestData::class);

    expect($parameters)->toHaveKeys(['password', 'password_confirmation'])
        ->and(crossFieldKeyAfter($parameters, 'password'))->toBe('password_confirmation')
        ->and($parameters['password']['description'])->toContain('A matching <b><i>password_confirmation</i></b> value must be sent with it.')
        ->and($parameters['password_confirmation']['type'])->toBe('string')
        ->and($parameters['password_confirmation']['description'])->toStartWith('Must match the value of <b><i>password</i></b>.')
        ->and($raw['password_confirmation']->location)->toBe($raw['password']->location);
});

it('publishes the companion of a confirmed rule string directly after its source', function () {
    $parameters = crossFieldParameters(CrossFieldRuleConfirmationTestData::class);

    expect(array_keys($parameters))->toBe(['password', 'password_confirmation', 'name', 'secret', 'secret_confirmation'])
        ->and($parameters['password_confirmation']['description'])->toStartWith('Must match the value of <b><i>password</i></b>.');
});

it('publishes one companion for a Confirmed followed by an equal confirmed rule string', function () {
    $parameters = crossFieldParameters(CrossFieldRuleConfirmationTestData::class);

    expect(array_keys($parameters))->toBe(['password', 'password_confirmation', 'name', 'secret', 'secret_confirmation'])
        ->and(crossFieldKeyAfter($parameters, 'secret'))->toBe('secret_confirmation');
});

it('publishes no companion for an array of Data source, but still its items', function () {
    $parameters = crossFieldParameters(CrossFieldArrayConfirmationTestData::class);

    expect($parameters)->toHaveKey('accounts[].password')
        ->not->toHaveKey('accounts_confirmation');
});

it('AC5: pins that Confirmed reports no custom name, which D2 depends on', function () {
    expect((new Confirmed())->parameters())->toBe([]);
});

it('AC5: publishes the companion name validation enforces, not the discarded argument', function () {
    $parameters = crossFieldParameters(CrossFieldCustomConfirmationTestData::class);

    expect($parameters)->toHaveKey('email_confirmation')
        ->and($parameters)->not->toHaveKey('email_repeat')
        ->and($parameters['email']['description'])->toContain('A matching <b><i>email_confirmation</i></b> value must be sent with it.');
})->skip(PHP_VERSION_ID < 80400, 'PHP 8.3 rejects an argument to a constructor-less attribute.');

it('AC5: on PHP 8.3 the discarded-argument declaration is rejected before anything is documented', function () {
    expect(fn() => crossFieldParameters(CrossFieldCustomConfirmationTestData::class))
        ->toThrow(Error::class, 'does not have a constructor, cannot pass arguments');
})->skip(PHP_VERSION_ID >= 80400, 'PHP 8.4 discards an argument to a constructor-less attribute.');

it('AC6: publishes the values that count as acceptance', function () {
    $parameters = crossFieldParameters(CrossFieldAcceptanceTestData::class);

    expect($parameters['terms_accepted']['description'])->toContain('Must be sent as one of ' . acceptedList() . '.');
});

it('AC7: states the condition of a conditional acceptance', function () {
    $parameters = crossFieldParameters(CrossFieldAcceptanceTestData::class);

    expect($parameters['marketing_opt_in']['description'])
        ->toContain('Must be accepted when <b><i>country</i></b> is <code>DE</code>, by sending one of ' . acceptedList() . '.');
});

it('AC8: documents refusal rules with their own value set', function () {
    $parameters = crossFieldParameters(CrossFieldAcceptanceTestData::class);

    expect($parameters['data_sharing']['description'])->toContain('Must be sent as one of ' . declinedList() . '.')
        ->and($parameters['auto_renew']['description'])
        ->toContain('Must be declined when <b><i>plan</i></b> is <code>trial</code>, by sending one of ' . declinedList() . '.');
});

it('AC9: combines a comparison with an existing sentence in a stable order', function () {
    $first = crossFieldParameters(CrossFieldComparisonTestData::class);
    $second = crossFieldParameters(CrossFieldComparisonTestData::class);

    expect($first['email_confirmation']['description'])
        ->toBe('Must be a string. Must be a valid email address. Must match the value of <b><i>email</i></b>.')
        ->and(array_map(fn($parameter) => $parameter['description'], $second))
        ->toBe(array_map(fn($parameter) => $parameter['description'], $first));
});

it('AC10: documents a reference to an undeclared field without breaking the build', function () {
    $parameters = crossFieldParameters(CrossFieldComparisonTestData::class);

    expect($parameters['dangling']['description'])->toContain('Must match the value of <b><i>missing_field</i></b>.')
        ->and($parameters['sibling']['description'])->toBe('Must be a string. A null value is accepted.');
});

it('AC11: mirrors the source requirement on the companion', function () {
    $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);

    expect($parameters['password_confirmation']['required'])->toBeTrue()
        ->and($parameters['password_confirmation']['nullable'])->toBeFalse()
        ->and($parameters['password_confirmation']['description'])->toBe('Must match the value of <b><i>password</i></b>.')
        ->and($parameters['recovery_pin_confirmation']['required'])->toBeFalse()
        ->and($parameters['recovery_pin_confirmation']['nullable'])->toBeTrue()
        ->and($parameters['recovery_pin_confirmation']['description'])->toBe(
            'Must match the value of <b><i>recovery_pin</i></b>. Required when <b><i>recovery_pin</i></b> is sent. A null value is accepted.'
        );
});

it('AC12: publishes examples that satisfy the acceptance and confirmation rules', function () {
    $acceptance = crossFieldParameters(CrossFieldAcceptanceTestData::class);

    expect($acceptance['terms_accepted']['example'])->toBeTrue()
        ->and($acceptance['data_sharing']['example'])->toBeFalse();

    foreach (range(1, 5) as $ignored) {
        $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);

        expect($parameters['password_confirmation']['example'])->toBe($parameters['password']['example']);
    }
});

it('copies type, location and constraints from the source, but not its default', function () {
    $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);
    $raw = crossFieldRaw(CrossFieldConfirmationTestData::class);

    expect($parameters['security_code_confirmation']['type'])->toBe('integer')
        ->and($raw['token']->location)->toBe(ParameterLocation::QUERY)
        ->and($raw['token_confirmation']->location)->toBe(ParameterLocation::QUERY)
        ->and($parameters['passphrase_confirmation']['custom']['openAPI']['minLength'])->toBe(12)
        ->and($parameters['defaulted']['custom']['openAPI'])->toHaveKey('default')
        ->and($parameters['defaulted_confirmation'])->not->toHaveKey('custom');
});

it('lets a declared property win over the companion, hidden or not', function () {
    $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);

    expect(array_count_values(array_keys($parameters))['username_confirmation'])->toBe(1)
        ->and($parameters['username_confirmation']['description'])->toBe('Must be a string.')
        ->and($parameters)->toHaveKey('alias')
        ->and($parameters['alias']['description'])->toContain('A matching <b><i>alias_confirmation</i></b> value must be sent with it.')
        ->and($parameters)->not->toHaveKey('alias_confirmation');
});

it('publishes no companion for a hidden source', function () {
    expect(crossFieldParameters(CrossFieldConfirmationTestData::class))
        ->not->toHaveKey('secret')
        ->not->toHaveKey('secret_confirmation');
});

it('places a nested or array companion beside its source', function () {
    $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);

    expect(crossFieldKeyAfter($parameters, 'profile.password'))->toBe('profile.password_confirmation')
        ->and(crossFieldKeyAfter($parameters, 'users[].password'))->toBe('users[].password_confirmation');
});

it('keeps the sentence but publishes no companion for a nested Data source', function () {
    $parameters = crossFieldParameters(CrossFieldConfirmationTestData::class);

    expect($parameters)->not->toHaveKey('settings_confirmation')
        ->and($parameters['settings']['description'])->toContain('A matching <b><i>settings_confirmation</i></b> value must be sent with it.')
        ->and(array_key_last($parameters))->toBe('after');
});

it('draws acceptance examples from the value set by type, unless an example is given', function () {
    $parameters = crossFieldParameters(CrossFieldAcceptanceTestData::class);

    expect($parameters['consent_text']['example'])->toBe('yes')
        ->and($parameters['opt_out_flag']['example'])->toBe(0)
        ->and($parameters['explicit_example']['example'])->toBeFalse();
});

it('publishes and exemplifies only the enum cases the acceptance rule takes', function (string $property, array $cases) {
    $rules = CrossFieldAcceptanceTestData::getValidationRules([])[$property];

    foreach (range(1, 20) as $ignored) {
        $parameter = crossFieldParameters(CrossFieldAcceptanceTestData::class)[$property];

        expect($parameter['enumValues'])->toBe($cases)
            ->and($cases)->toContain($parameter['example'])
            ->and(validator([$property => $parameter['example']], [$property => $rules])->passes())->toBeTrue();
    }
})->with([
    'Accepted on a string enum' => ['enum_yes_no', ['yes']],
    'Accepted before an In'     => ['enum_accepted_in', ['yes']],
    'Declined after an In'      => ['enum_in_declined', ['no']],
    'Declined on an int enum'   => ['enum_int_declined', [0]],
    'Accepted on an int enum'   => ['enum_int_accepted', [1]],
]);

it('states an empty allowed set for an enum no case of which the acceptance rule takes', function () {
    $parameter = crossFieldParameters(CrossFieldAcceptanceTestData::class)['enum_consent'];
    $rules = CrossFieldAcceptanceTestData::getValidationRules([])['enum_consent'];

    expect($parameter)->not->toHaveKey('enumValues')
        ->and($parameter['description'])->toContain('Note: the list of allowed values is empty')
        ->and(validator(['enum_consent' => 'agreed'], ['enum_consent' => $rules])->passes())->toBeFalse()
        ->and(validator(['enum_consent' => 'refused'], ['enum_consent' => $rules])->passes())->toBeFalse();
});

it('publishes only values Laravel accepts or declines', function () {
    foreach (AcceptanceProcessor::ACCEPTED_VALUES as $value) {
        expect(validator(['f' => $value], ['f' => 'accepted'])->passes())->toBeTrue();
    }

    foreach (AcceptanceProcessor::DECLINED_VALUES as $value) {
        expect(validator(['f' => $value], ['f' => 'declined'])->passes())->toBeTrue();
    }

    foreach (['TRUE', 'Yes', 'ON', 2] as $value) {
        expect(validator(['f' => $value], ['f' => 'accepted'])->passes())->toBeFalse();
    }

    foreach (['FALSE', 'No', 'OFF'] as $value) {
        expect(validator(['f' => $value], ['f' => 'declined'])->passes())->toBeFalse();
    }
});

class CrossFieldComparisonTestData extends Data
{
    public function __construct(
        #[Email]
        public string $email,
        #[Email, Same('email')]
        public string $email_confirmation,
        public string $current_password,
        #[Different('current_password')]
        public string $new_password,
        /** @var array<string> */
        public array $tags,
        #[InArray('tags.*')]
        public string $primary_tag,
        #[InArray('tags')]
        public ?string $legacy_tag = null,
        #[Same('missing_field')]
        public ?string $dangling = null,
        public ?string $sibling = null,
    ) {}
}

class CrossFieldConfirmationChildData extends Data
{
    public function __construct(
        #[Confirmed]
        public string $password,
    ) {}
}

/**
 * Kept apart: PHP 8.3 cannot instantiate this attribute, so the whole class is unreadable there.
 */
class CrossFieldCustomConfirmationTestData extends Data
{
    public function __construct(
        #[Confirmed('email_repeat')]
        public string $email,
    ) {}
}

class CrossFieldRuleConfirmationTestData extends Data
{
    public function __construct(
        #[Rule('confirmed')]
        public string $password,
        public string $name,
        #[Confirmed, Rule('confirmed')]
        public string $secret,
    ) {}
}

class CrossFieldArrayConfirmationTestData extends Data
{
    /** @param CrossFieldConfirmationChildData[] $accounts */
    public function __construct(
        #[Confirmed]
        public array $accounts,
    ) {}
}

class CrossFieldConfirmationTestData extends Data
{
    public function __construct(
        #[Confirmed]
        public string $password,
        #[Confirmed]
        public int $security_code,
        #[Confirmed]
        public string $username,
        public string $username_confirmation,
        #[Confirmed, Hidden]
        public string $secret,
        #[Confirmed]
        public string $alias,
        #[Hidden]
        public string $alias_confirmation,
        #[Confirmed, Min(12)]
        public string $passphrase,
        public CrossFieldConfirmationChildData $profile,
        /** @var array<CrossFieldConfirmationChildData> */
        public array $users,
        #[Confirmed]
        public ?string $recovery_pin = null,
        #[Confirmed, QueryParameter]
        public ?string $token = null,
        #[Confirmed]
        public string $defaulted = 'x',
        #[Confirmed]
        public ?CrossFieldConfirmationChildData $settings = null,
        public string $after = 'end',
    ) {}
}

enum CrossFieldConsent: string
{
    case Agreed = 'agreed';
    case Refused = 'refused';
}

enum CrossFieldYesNo: string
{
    case Yes = 'yes';
    case No = 'no';
}

enum CrossFieldIntFlag: int
{
    case Off = 0;
    case On = 1;
}

class CrossFieldAcceptanceTestData extends Data
{
    public function __construct(
        #[Accepted]
        public bool $terms_accepted,
        #[Declined]
        public bool $data_sharing,
        #[Accepted]
        public string $consent_text,
        #[Declined]
        public int $opt_out_flag,
        #[Accepted, Example(false)]
        public bool $explicit_example,
        #[Accepted]
        public CrossFieldConsent $enum_consent,
        #[Accepted]
        public CrossFieldYesNo $enum_yes_no,
        #[Accepted, In(['yes', 'no'])]
        public CrossFieldYesNo $enum_accepted_in,
        #[In(['yes', 'no']), Declined]
        public CrossFieldYesNo $enum_in_declined,
        #[Declined]
        public CrossFieldIntFlag $enum_int_declined,
        #[Accepted]
        public CrossFieldIntFlag $enum_int_accepted,
        #[AcceptedIf('country', 'DE')]
        public ?bool $marketing_opt_in = null,
        #[DeclinedIf('plan', 'trial')]
        public ?bool $auto_renew = null,
    ) {}
}

it('documents a consumer subclass of Same as Same, as the validator enforces it', function () {
    $parameters = crossFieldParameters(CrossFieldSameSubclassTestData::class);
    $rules = CrossFieldSameSubclassTestData::getValidationRules(['other' => 'a', 'mirror' => 'b']);

    expect($parameters['mirror']['description'])->toContain('Must match the value of <b><i>other</i></b>.')
        ->and($rules['mirror'])->toContain('same:other')
        ->and(validator(['other' => 'a', 'mirror' => 'b'], $rules)->passes())->toBeFalse()
        ->and(validator(['other' => 'a', 'mirror' => 'a'], $rules)->passes())->toBeTrue();
});

it('documents a consumer subclass of Accepted as Accepted, required and not nullable', function () {
    $parameters = crossFieldParameters(CrossFieldAcceptedSubclassTestData::class);
    $rules = CrossFieldAcceptedSubclassTestData::getValidationRules([]);

    expect($parameters['agreed']['description'])->toContain('Must be sent as one of ' . acceptedList() . '.')
        ->and($parameters['agreed']['required'])->toBeTrue()
        ->and($parameters['agreed']['nullable'])->toBeFalse()
        ->and(validator([], $rules)->passes())->toBeFalse()
        ->and(validator(['agreed' => null], $rules)->passes())->toBeFalse()
        ->and(validator(['agreed' => true], $rules)->passes())->toBeTrue();
});

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CrossFieldAcceptedSubclass extends Accepted {}

class CrossFieldAcceptedSubclassTestData extends Data
{
    public function __construct(
        #[CrossFieldAcceptedSubclass]
        public ?bool $agreed,
    ) {}
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class CrossFieldSameSubclass extends Same {}

class CrossFieldSameSubclassTestData extends Data
{
    public function __construct(
        public string $other,
        #[CrossFieldSameSubclass('other')]
        public string $mirror,
    ) {}
}
