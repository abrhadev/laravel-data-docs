<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Alpha;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\IP;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Lowercase;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\Uppercase;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

enum InTestRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
}

enum InTestStage: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Published = 'Published';
}

enum InTestPure
{
    case Alpha;
    case Beta;
}

function inThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(InTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function passesInRules(string $property, mixed $value): bool
{
    $rules = InTestData::getValidationRules([])[$property];

    return Validator::make([$property => $value], [$property => $rules])->passes();
}

it('publishes the values an In accepts as the allowed-value set', function (string $property, ?array $enum, mixed $outside) {
    $published = inThroughPipeline($property)->toParameter()->enumValues;

    expect($published)->toBe($enum);

    foreach ($enum ?? [] as $value) {
        $sent = str_ends_with(inThroughPipeline($property)->type ?? '', '[]') ? [$value] : $value;
        expect(passesInRules($property, $sent))->toBeTrue();
    }

    expect(passesInRules($property, $outside))->toBeFalse();
})->with([
    'strings (AC1)'                       => ['status', ['pending', 'shipped', 'cancelled'], 'PENDING'],
    'integers'                            => ['count', [1, 2, 3], 5],
    'floats'                              => ['ratio', [1.5, 2.5], '1.50'],
    'an enum-typed property (AC10)'       => ['role', ['editor', 'viewer'], 'admin'],
    'array items'                         => ['tags', ['a', 'b'], ['a', 'z']],
    'backed enum values'                  => ['enum_values', ['editor', 'viewer'], 'admin'],
    'a pure enum value'                   => ['pure_values', ['Alpha'], 'Beta'],
    'a value with a comma'                => ['with_comma', ['a,b', 'c'], 'a'],
    'a rule string'                       => ['from_rule', ['a', 'b'], 'c'],
    'a single value (AC9)'                => ['channel', ['email'], 'sms'],
    'a value the type cannot have'        => ['int_with_text', [1], 2],
    'an integer with a leading zero'      => ['int_leading_zero', [2], 1],
    'a float with a trailing zero'        => ['float_trailing_zero', [2.5], 1.5],
    'booleans'                            => ['flag', [true, false], 'yes'],
    'booleans written as 0 and 1'         => ['flag_ints', [true], false],
    'a single boolean'                    => ['flag_true', [true], false],
    'a value the pattern rejects'         => ['pattern_filtered', ['USD'], 'eur'],
    'an underscore Lowercase accepts'     => ['lower_underscore', ['in_progress', 'done'], 'Done'],
    'a dash Lowercase accepts'            => ['lower_dash', ['en-us'], 'EN-US'],
    'a non-ASCII letter Alpha accepts'    => ['alpha_unicode', ['café', 'tea'], 'tea2'],
    'two pattern rules, Alpha first'      => ['alpha_lower', ['abc'], 'ab1'],
    'two pattern rules, Lowercase first'  => ['lower_alpha', ['abc'], 'ABC'],
    'a prefix and a character class'      => ['starts_lower', ['abc'], 'xbc'],
    'two pattern rules rejecting all'     => ['upper_starts', null, 'usd'],
    'a value longer than Max'             => ['in_max', ['ab'], 'abcdef'],
    'a value shorter than Min'            => ['in_min', ['bbbb'], 'a'],
    'an integer above Max'                => ['in_int_max', [1], 50],
    'an integer MultipleOf rejects'       => ['in_multiple', [3, 6], 4],
    'a digit float MultipleOf rejects'    => ['in_digit_multiple', [102.0], 101],
    'a value Date rejects'                => ['in_date', ['2024-01-31'], 'nope'],
    'a value DateFormat rejects'          => ['in_date_format', ['2024-01-31'], '31/01/2024'],
    'a value only the digit rule rejects' => ['in_digits_prefixed', ['123'], '1ab'],
    'a password its classes reject'       => ['in_password', ['Abcdefgh12!x'], 'abcdefghijkl'],
    'a value Accepted rejects'            => ['in_accepted', ['on'], 'maybe'],
    'a value Declined rejects'            => ['in_declined', ['off'], 'nah'],
    'false beside Declined'               => ['bool_false_declined', [false], true],
    'booleans beside Declined'            => ['bool_declined', [false], true],
    'booleans after Declined'             => ['bool_declined_first', [false], true],
    'false and no after Declined'         => ['bool_declined_no', [false], true],
    'booleans beside Accepted'            => ['bool_accepted', [true], false],
    'booleans after Accepted'             => ['bool_accepted_first', [true], false],
    'integers beside Declined'            => ['int_declined', [0], 1],
    'integers after Accepted'             => ['int_accepted_first', [1], 0],
    'a value an ASCII regex rejects'      => ['in_regex_ascii', ['tea'], 'café'],
    'a value a Unicode regex accepts'     => ['in_regex_unicode', ['café', 'tea'], 'x y'],
    'digits of the wrong count'           => ['in_digits', ['007'], '1234'],
    'a value Email rejects'               => ['in_email', ['a@b.co'], 'bad'],
    'a value IP rejects'                  => ['in_ip', ['::1', '10.0.0.1'], 'bad'],
    'a value Uuid rejects'                => ['in_uuid', ['123e4567-e89b-12d3-a456-426614174000'], 'nope'],
    'a value Json rejects'                => ['in_json', ['{"a":1}'], '{'],
    'a URL of another protocol'           => ['in_url', ['https://example.com'], 'ftp://example.com'],
    'an empty In'                         => ['none', null, 'x'],
    'an enum no case of which is allowed' => ['role_none', null, 'admin'],
]);

it('pins how Laravel compares booleans: true as 1, false as an empty string', function () {
    // #[In([true, false])] becomes in:"1","", which a JSON false passes;
    // #[In([0, 1])] becomes in:"0","1", which only 0 or "0" passes, not false.
    expect(passesInRules('flag', true))->toBeTrue()
        ->and(passesInRules('flag', false))->toBeTrue()
        ->and(passesInRules('flag', '0'))->toBeFalse()
        ->and(passesInRules('flag_ints', true))->toBeTrue()
        ->and(passesInRules('flag_ints', false))->toBeFalse()
        ->and(passesInRules('flag_ints', '0'))->toBeTrue();
});

it('states the allowed values in the description', function (string $property, string $sentence) {
    expect(inThroughPipeline($property)->description)->toContain($sentence);
})->with([
    'a plain field'                => ['status', 'Must be one of: <code>pending</code>, <code>shipped</code>, <code>cancelled</code>.'],
    'array items'                  => ['tags', 'Each item must be one of: <code>a</code>, <code>b</code>.'],
    'an empty In'                  => ['none', 'Note: the list of allowed values is empty, so any request that sends this field fails validation.'],
    'an enum with no allowed case' => ['role_none', 'Note: the list of allowed values is empty'],
    'booleans as sent'             => ['flag', 'Must be one of: <code>true</code>, <code>false</code>.'],
    'a 0 only 0 or "0" passes'     => ['flag_ints', 'Must be one of: <code>"0"</code>, <code>true</code>.'],
    'a value the pattern rejects'  => ['pattern_filtered', 'Must be one of: <code>USD</code>.'],
]);

it('lists only the allowed enum cases on an enum-typed property (AC10)', function () {
    $description = inThroughPipeline('role')->description;

    expect($description)->toContain('<code>Editor</code> (editor)')
        ->toContain('<code>Viewer</code> (viewer)')
        ->not->toContain('Admin');
});

it('keeps the other attributes\' sentences next to the allowed values (AC8)', function () {
    $context = inThroughPipeline('currency');

    expect($context->toParameter()->enumValues)->toBe(['EUR', 'USD'])
        ->and($context->description)->toContain('Must contain only uppercase letters.');
});

it('gives an example the In accepts', function (string $property) {
    foreach (range(1, 25) as $run) {
        expect(passesInRules($property, inThroughPipeline($property)->example))->toBeTrue();
    }
})->with(['status', 'count', 'ratio', 'role', 'tags', 'enum_values', 'from_rule', 'channel', 'currency', 'int_leading_zero', 'float_trailing_zero', 'flag', 'flag_ints', 'flag_true', 'pattern_filtered', 'lower_underscore', 'lower_dash', 'alpha_unicode', 'alpha_lower', 'lower_alpha', 'starts_lower', 'flag_zero', 'flag_zero_string', 'in_max', 'in_min', 'in_int_max', 'in_multiple', 'in_regex_ascii', 'in_regex_unicode', 'in_digits', 'in_email', 'in_ip', 'in_uuid', 'in_json', 'in_url', 'in_date', 'in_date_format', 'in_digits_prefixed', 'in_password', 'in_accepted', 'in_declined', 'bool_false_declined', 'bool_declined', 'bool_declined_first', 'bool_declined_no', 'bool_accepted', 'bool_accepted_first', 'int_declined', 'int_accepted_first']);

it('leaves out a published pattern that rejects an allowed value, which would make the schema unsatisfiable', function () {
    $mixed = inThroughPipeline('lower_underscore')->toParameter()->openApiAttributes;
    $matching = inThroughPipeline('pattern_filtered')->toParameter()->openApiAttributes;

    expect($mixed)->not->toHaveKey('pattern')
        ->and($matching['pattern'] ?? null)->toBe('^[A-Z]+$');
});

it('keeps the pattern of an enum-typed field, whose case list the pattern does not narrow', function () {
    // StartsWith('e') rejects admin and viewer, which the published enum still lists.
    expect(inThroughPipeline('role_prefixed')->toParameter()->openApiAttributes['pattern'] ?? null)->toBe('^(e)')
        ->and(passesInRules('role_prefixed', 'viewer'))->toBeFalse()
        ->and(passesInRules('role_prefixed', 'editor'))->toBeTrue();
});

it('checks allowed values against a password rule without its custom rules, which may read the rest of the request', function () {
    // confirmed reads a companion field the lone value never has.
    Illuminate\Validation\Rules\Password::defaults(fn() => Illuminate\Validation\Rules\Password::min(8)->rules(['confirmed']));

    try {
        expect(inThroughPipeline('in_password_default')->toParameter()->enumValues)->toBe(['abcdefgh1', 'abcdefgh2']);
    } finally {
        Illuminate\Validation\Rules\Password::defaults(fn() => Illuminate\Validation\Rules\Password::min(8));
    }
});

it('decides an enum field\'s approximate pattern by every case Laravel accepts, the same in every build', function () {
    // lowercase accepts in_progress, which ^[a-z]+$ rejects; Published fails lowercase and decides nothing.
    foreach (range(1, 20) as $run) {
        expect(inThroughPipeline('stage_lower')->toParameter()->openApiAttributes)->not->toHaveKey('pattern')
            ->and(inThroughPipeline('stage_upper')->toParameter()->openApiAttributes['pattern'] ?? null)->toBe('^[A-Z]+$');
    }
});

it('keeps an approximate pattern beside an explicit example Laravel rejects', function () {
    expect(inThroughPipeline('invalid_example_lower')->toParameter()->openApiAttributes['pattern'] ?? null)->toBe('^[a-z]+$')
        ->and(passesInRules('invalid_example_lower', 'ABC'))->toBeFalse();
});

it('keeps an approximate pattern beside an explicit example or case Laravel rejects for a bound or the In set', function (string $property, string $pattern, mixed $rejected) {
    foreach (range(1, 10) as $run) {
        expect(inThroughPipeline($property)->toParameter()->openApiAttributes['pattern'] ?? null)->toBe($pattern);
    }

    expect(passesInRules($property, $rejected))->toBeFalse();
})->with([
    'an enum case longer than Max'       => ['stage_lower_max', '^[a-z]+$', 'in_progress'],
    'an example longer than Max'         => ['lower_max_example', '^[a-z]+$', 'in_progress'],
    'a Unicode example longer than Max'  => ['cafebar_max_example', '^[a-z]+$', 'cafébar'],
    'a Unicode example shorter than Min' => ['upper_min_example', '^[A-Z]+$', 'ÄBCDEF'],
    'an example outside the In set'      => ['cafe_in_example', '^[a-z]+$', 'café'],
]);

it('still leaves out an approximate pattern when a value Laravel accepts on every rule rejects it', function (string $property, mixed $accepted) {
    expect(inThroughPipeline($property)->toParameter()->openApiAttributes)->not->toHaveKey('pattern')
        ->and(passesInRules($property, $accepted))->toBeTrue();
})->with([
    'an enum case within Min'      => ['stage_lower_min', 'in_progress'],
    'a Unicode example within Max' => ['cafe_max_example', 'café'],
    'a Unicode example in the set' => ['cafe_in_set_example', 'café'],
]);

it('publishes the same boolean set after the validation rules were built', function () {
    // Spatie's getRule() rewrites In's values in place on a cached attribute, false becoming ''.
    InTestData::getValidationRules([]);

    expect(inThroughPipeline('flag')->toParameter()->enumValues)->toBe([true, false]);
});

class InTestData extends Data
{
    /** @param string[] $tags */
    public function __construct(
        #[In(['pending', 'shipped', 'cancelled'])]
        public string $status,
        #[In([1, 2, 3])]
        public int $count,
        #[In([1.5, 2.5])]
        public float $ratio,
        #[In(['editor', 'viewer'])]
        public InTestRole $role,
        #[In(['a', 'b'])]
        public array $tags,
        #[In(InTestRole::Editor, InTestRole::Viewer)]
        public string $enum_values,
        #[In(InTestPure::Alpha)]
        public string $pure_values,
        #[In(['a,b', 'c'])]
        public string $with_comma,
        #[Rule('in:a,b')]
        public string $from_rule,
        #[In(['email'])]
        public string $channel,
        #[In(['1', 'abc'])]
        public int $int_with_text,
        #[In(['01', '2'])]
        public int $int_leading_zero,
        #[In(['1.50', '2.5'])]
        public float $float_trailing_zero,
        #[In([true, false])]
        public bool $flag,
        #[In([0, 1])]
        public bool $flag_ints,
        #[In([true, 'true'])]
        public bool $flag_true,
        #[In(['eur', 'USD']), Uppercase]
        public string $pattern_filtered,
        #[In(['in_progress', 'done', 'Done']), Lowercase]
        public string $lower_underscore,
        #[In(['en-us', 'EN-US']), Lowercase]
        public string $lower_dash,
        #[In(['café', 'tea', 'tea2']), Alpha]
        public string $alpha_unicode,
        #[StartsWith('e')]
        public InTestRole $role_prefixed,
        #[Lowercase]
        public InTestStage $stage_lower,
        #[Uppercase]
        public InTestStage $stage_upper,
        #[Abrha\LaravelDataDocs\Attributes\Example('ABC'), Lowercase]
        public string $invalid_example_lower,
        #[In(['ab', 'abcdef']), Max(3)]
        public string $in_max,
        #[In(['a', 'bbbb']), Min(3)]
        public string $in_min,
        #[In(['1', '50']), Max(10)]
        public int $in_int_max,
        #[In(['3', '4', '6']), MultipleOf(3)]
        public int $in_multiple,
        #[Digits(3), MultipleOf(1.5), In(['101', '102'])]
        public float $in_digit_multiple,
        #[In(['2024-01-31', 'nope']), Date]
        public string $in_date,
        #[In(['2024-01-31', '31/01/2024']), DateFormat('Y-m-d')]
        public string $in_date_format,
        #[StartsWith('1'), Digits(3), In(['1ab', '123'])]
        public string $in_digits_prefixed,
        #[In(['abcdefghijkl', 'Abcdefgh12!x']), Password(min: 12, mixedCase: true, numbers: true, symbols: true)]
        public string $in_password,
        #[In(['abcdefgh1', 'abcdefgh2', 'short']), Password(default: true)]
        public string $in_password_default,
        #[In(['on', 'maybe']), Spatie\LaravelData\Attributes\Validation\Accepted]
        public string $in_accepted,
        #[Spatie\LaravelData\Attributes\Validation\Declined, In(['off', 'nah'])]
        public string $in_declined,
        #[In(['café', 'tea']), Regex('/^\w+$/')]
        public string $in_regex_ascii,
        #[In(['café', 'tea']), Regex('/^\w+$/u')]
        public string $in_regex_unicode,
        #[Digits(3), In(['007', '1234'])]
        public string $in_digits,
        #[In(['a@b.co', 'bad']), Email]
        public string $in_email,
        #[In(['::1', '10.0.0.1', 'bad']), IP]
        public string $in_ip,
        #[In(['123e4567-e89b-12d3-a456-426614174000', 'nope']), Uuid]
        public string $in_uuid,
        #[In(['{"a":1}', '{']), Json]
        public string $in_json,
        #[In(['https://example.com', 'ftp://example.com', 'nope']), Url('https')]
        public string $in_url,
        #[In([0])]
        public bool $flag_zero,
        #[In(['0'])]
        public bool $flag_zero_string,
        #[In(['abc', 'ab1']), Alpha, Lowercase]
        public string $alpha_lower,
        #[In(['ABC', 'abc']), Lowercase, Alpha]
        public string $lower_alpha,
        #[In(['abc', 'xbc']), StartsWith('a'), Lowercase]
        public string $starts_lower,
        #[In(['usd', 'EUR']), Uppercase, StartsWith('u')]
        public string $upper_starts,
        #[In([])]
        public string $none,
        #[In(['nope'])]
        public InTestRole $role_none,
        #[In(['EUR', 'USD']), Uppercase]
        public string $currency,
        #[In([false]), Declined]
        public bool $bool_false_declined,
        #[In([true, false]), Declined]
        public bool $bool_declined,
        #[Declined, In([true, false])]
        public bool $bool_declined_first,
        #[Declined, In([false, 'no'])]
        public bool $bool_declined_no,
        #[In([true, false]), Accepted]
        public bool $bool_accepted,
        #[Accepted, In([true, false])]
        public bool $bool_accepted_first,
        #[In([0, 1]), Declined]
        public int $int_declined,
        #[Accepted, In([0, 1])]
        public int $int_accepted_first,
        #[Lowercase, Max(5)]
        public InTestStage $stage_lower_max,
        #[Lowercase, Min(6)]
        public InTestStage $stage_lower_min,
        #[Lowercase, Max(5), Abrha\LaravelDataDocs\Attributes\Example('in_progress')]
        public string $lower_max_example,
        #[Abrha\LaravelDataDocs\Attributes\Example('cafébar'), Lowercase, Max(3)]
        public string $cafebar_max_example,
        #[Abrha\LaravelDataDocs\Attributes\Example('café'), Lowercase, Max(5)]
        public string $cafe_max_example,
        #[Abrha\LaravelDataDocs\Attributes\Example('ÄBCDEF'), Uppercase, Min(10)]
        public string $upper_min_example,
        #[Abrha\LaravelDataDocs\Attributes\Example('café'), Lowercase, In(['abc', 'xyz'])]
        public string $cafe_in_example,
        #[Abrha\LaravelDataDocs\Attributes\Example('café'), Lowercase, In(['café', 'abc'])]
        public string $cafe_in_set_example,
    ) {}
}
