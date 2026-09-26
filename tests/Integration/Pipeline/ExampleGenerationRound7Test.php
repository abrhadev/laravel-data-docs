<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Alpha;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\EndsWith;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IP;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\Lowercase;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\Uppercase;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

const ROUND7_CUSTOM_TYPES = ['custom_types' => [
    'ExampleGenerationRound7Code[]' => ['type' => 'string[]', 'descriptions' => ['A code.'], 'pattern' => '^[A-Z]{3}$'],
    'ExampleGenerationRound7Qty[]'  => ['type' => 'integer[]', 'descriptions' => ['A quantity.'], 'minimum' => 10, 'maximum' => 20, 'multipleOf' => 3],
]];

function round7ThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ExampleGenerationRound7TestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault(ROUND7_CUSTOM_TYPES)->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

/**
 * Builds the property many times; every example must pass the Data class's
 * own rules, as the PHP value and as the JSON a client sends, and match the
 * pattern the same build publishes.
 */
function round7ExpectPassingExamples(string $property, int $runs = 40): void
{
    $rules = ExampleGenerationRound7TestData::getValidationRules([])[$property];

    foreach (range(1, $runs) as $run) {
        $context = round7ThroughPipeline($property);
        $example = $context->example;
        $pattern = $context->toParameter()->openApiAttributes['pattern'] ?? null;
        $shown = "{$property}: " . var_export($example, true);

        expect(Validator::make([$property => $example], [$property => $rules])->passes())->toBeTrue($shown)
            ->and(Validator::make([$property => json_decode(json_encode($example))], [$property => $rules])->passes())->toBeTrue($shown . ' after JSON');

        if ($pattern !== null && is_string($example)) {
            expect(preg_match("\x01{$pattern}\x01u", $example))->toBe(1, "{$shown} against {$pattern}");
        }
    }
}

it('gives a numeric string with a whole divisor a multiple within its caps, zero when it is the only one', function (string $property) {
    round7ExpectPassingExamples($property);
})->with([
    'upper below the divisor beside a \d regex' => 'mult5_lt3_digits_regex',
    'digit count, upper below the divisor'      => 'digits3_mult5_lt3',
    'digit count, large divisor below its cap'  => 'digits3_mult250_lt200',
    'divisor above a one-character cap'         => 'mult10_size1',
    'divisor above a three-character cap'       => 'mult1000_max3',
    'divisor above a two-character cap'         => 'mult101_max2',
    'divisor above a two-digit count'           => 'mult150_digits2',
    'explicit zero lower bound'                 => 'mult1000_max3_gte0',
    'upper below zero'                          => 'mult250_lt0',
    'upper above the divisor'                   => 'mult5_lt8_regex',
]);

it('gives a numeric string an example its pattern rules accept', function (string $property) {
    round7ExpectPassingExamples($property);
})->with([
    'three-digit regex, no length bound'   => 'mult5_regex3',
    'four digits with no leading zero'     => 'mult5_regex_nonzero4',
    'prefix'                               => 'mult5_starts2',
    'suffix'                               => 'mult5_ends0',
    'prefix on a comparison-only string'   => 'gt5_starts9',
    'fractional divisor beside Digits'     => 'digits3_mult15',
    'half beside four digits'              => 'mult05_digits4',
    'fractional divisor beside a range'    => 'digitsbetween_mult25',
    'half beside a \d regex'               => 'mult05_digits_regex',
    'quarter beside a \d regex'            => 'mult025_digits_regex',
    'fractional divisor with a min length' => 'mult15_min3',
    'fractional divisor and a prefix'      => 'mult05_starts1',
]);

it('keeps a fractional divisor within a one-character length', function (string $property) {
    round7ExpectPassingExamples($property, 60);
})->with(['mult07_max1', 'mult03_max1', 'mult01_max1', 'mult05_size1', 'mult07_max3']);

it('gives a number field a multiple of a divisor larger than the default range', function (string $property) {
    round7ExpectPassingExamples($property);
})->with([
    'fractional divisor, no bound'      => 'float_mult2505',
    'negative divisor, no bound'        => 'int_mult_neg500',
    'published divisor above a minimum' => 'int_min1_mult500',
    'published divisor below zero'      => 'int_mult250_lt0',
    'float with a published divisor'    => 'float_mult150_gt0',
    'fractional divisor below zero'     => 'float_mult2505_lt0',
    'negative divisor below zero'       => 'int_mult_neg250_lt0',
    'small divisor, no bound'           => 'int_mult5',
    'small divisor below a small upper' => 'int_mult5_lt3',
]);

it('draws a pattern example its own pattern and rules accept', function (string $property) {
    round7ExpectPassingExamples($property);
})->with([
    'negated word class'          => 'not_word',
    'negated POSIX class'         => 'not_alnum_posix',
    'negated letters and digits'  => 'not_alnum',
    'negated letters'             => 'not_lower',
    'negated digits and a space'  => 'not_digit_space',
    'negated quotes'              => 'not_quotes',
    'open-ended count'            => 'digits_open',
    'open-ended letters'          => 'letters_open',
    'open-ended then a digit'     => 'letters_open_digit',
    'open-ended with a max'       => 'upper_open_max',
    'open-ended beside a prefix'  => 'starts_letters_open',
    'open-ended group'            => 'group_open',
    'open-ended from zero'        => 'letters_open_zero',
    'lookaheads'                  => 'password_lookahead',
    'negative lookahead'          => 'not_admin',
    'inline flag'                 => 'inline_flag',
    'escaped brackets'            => 'escaped_brackets',
    'non-word class'              => 'non_word',
    'nested group'                => 'nested_group',
    'hex escape'                  => 'hex_escape',
    'negated Unicode class'       => 'not_letter_unicode',
    'closed count'                => 'closed_count',
    'optional letters'            => 'optional_letters',
    'alternation'                 => 'alternation',
    'case-insensitive open-ended' => 'digits_open_i',
]);

it('fits an email domain suffix over the drawn domain', function (string $property) {
    round7ExpectPassingExamples($property);
})->with(['email_ends_domain', 'email_regex_domain', 'email_regex_domain_i', 'email_ends_org', 'email_starts_a']);

it('gives a format with a prefix or suffix and a case rule an example Laravel accepts, and publishes the same pattern in every build', function (string $property) {
    round7ExpectPassingExamples($property);

    $patterns = array_map(fn() => round7ThroughPipeline($property)->toParameter()->openApiAttributes['pattern'] ?? null, range(1, 20));

    expect(array_unique($patterns))->toHaveCount(1);
})->with(['url_ends_pdf_lower', 'url_lower_regex_pdf', 'ip_starts_10_lower', 'uuid_starts_a_upper', 'email_starts_a_lower']);

it('gives an array example as many items as its bounds ask for', function (string $property, int $count) {
    round7ExpectPassingExamples($property, 20);

    expect(round7ThroughPipeline($property)->example)->toHaveCount($count);
})->with([
    'string[] Min(5)'            => ['strings_min5', 5],
    'string[] Size(4)'           => ['strings_size4', 4],
    'int[] Min(5)'               => ['ints_min5', 5],
    'float[] Min(4) Max(6)'      => ['floats_between', 4],
    'bool[] Size(5)'             => ['bools_size5', 5],
    'enum[] Min(4)'              => ['enums_min4', 4],
    'In string[] Min(4)'         => ['in_min4', 4],
    'custom type Min(5)'         => ['codes_min5', 5],
    'custom type Size(4)'        => ['codes_size4', 4],
    'custom integer type Min(4)' => ['qtys_min4', 4],
    'string[] no bound'          => ['strings', 1],
    'string[] Max(2)'            => ['strings_max2', 1],
]);

it('keeps the neighbouring scalar examples valid', function (string $property) {
    round7ExpectPassingExamples($property);
})->with(['plain_bool', 'plain_int', 'plain_float', 'plain_string', 'plain_enum', 'string_mult5', 'string_mult5_digits4', 'string_lt3']);

enum ExampleGenerationRound7Colour: string
{
    case Red = 'red';
    case Green = 'green';
    case Blue = 'blue';
}

class ExampleGenerationRound7Code {}

class ExampleGenerationRound7Qty {}

class ExampleGenerationRound7TestData extends Data
{
    /**
     * @param string[] $strings
     * @param string[] $strings_min5
     * @param string[] $strings_size4
     * @param string[] $strings_max2
     * @param int[] $ints_min5
     * @param float[] $floats_between
     * @param bool[] $bools_size5
     * @param ExampleGenerationRound7Colour[] $enums_min4
     * @param string[] $in_min4
     * @param ExampleGenerationRound7Code[] $codes_min5
     * @param ExampleGenerationRound7Code[] $codes_size4
     * @param ExampleGenerationRound7Qty[] $qtys_min4
     */
    public function __construct(
        #[MultipleOf(5), LessThan(3), Regex('/^\d+$/')]
        public string $mult5_lt3_digits_regex,
        #[Digits(3), MultipleOf(5), LessThan(3)]
        public string $digits3_mult5_lt3,
        #[Digits(3), MultipleOf(250), LessThan(200)]
        public string $digits3_mult250_lt200,
        #[MultipleOf(10), Size(1)]
        public string $mult10_size1,
        #[MultipleOf(1000), Max(3)]
        public string $mult1000_max3,
        #[MultipleOf(101), Max(2)]
        public string $mult101_max2,
        #[MultipleOf(150), Digits(2)]
        public string $mult150_digits2,
        #[MultipleOf(1000), Max(3), GreaterThanOrEqualTo(0)]
        public string $mult1000_max3_gte0,
        #[MultipleOf(250), LessThan(0)]
        public string $mult250_lt0,
        #[MultipleOf(5), LessThan(8), Regex('/^\d+$/')]
        public string $mult5_lt8_regex,
        #[MultipleOf(5), Regex('/^\d{3}$/')]
        public string $mult5_regex3,
        #[MultipleOf(5), Regex('/^[1-9]\d{3}$/')]
        public string $mult5_regex_nonzero4,
        #[MultipleOf(5), StartsWith('2')]
        public string $mult5_starts2,
        #[MultipleOf(5), EndsWith('0')]
        public string $mult5_ends0,
        #[GreaterThan(5), StartsWith('9')]
        public string $gt5_starts9,
        #[Digits(3), MultipleOf(1.5)]
        public string $digits3_mult15,
        #[MultipleOf(0.5), Digits(4)]
        public string $mult05_digits4,
        #[DigitsBetween(2, 4), MultipleOf(2.5)]
        public string $digitsbetween_mult25,
        #[MultipleOf(0.5), Regex('/^\d+$/')]
        public string $mult05_digits_regex,
        #[MultipleOf(0.25), Regex('/^\d+$/')]
        public string $mult025_digits_regex,
        #[MultipleOf(1.5), Min(3)]
        public string $mult15_min3,
        #[MultipleOf(0.5), StartsWith('1')]
        public string $mult05_starts1,
        #[MultipleOf(0.7), Max(1)]
        public string $mult07_max1,
        #[MultipleOf(0.3), Max(1)]
        public string $mult03_max1,
        #[MultipleOf(0.1), Max(1)]
        public string $mult01_max1,
        #[MultipleOf(0.5), Size(1)]
        public string $mult05_size1,
        #[MultipleOf(0.7), Max(3)]
        public string $mult07_max3,
        #[MultipleOf(250.5)]
        public float $float_mult2505,
        #[MultipleOf(-500)]
        public int $int_mult_neg500,
        #[Min(1), MultipleOf(500)]
        public int $int_min1_mult500,
        #[MultipleOf(250), LessThan(0)]
        public int $int_mult250_lt0,
        #[MultipleOf(150), GreaterThan(0)]
        public float $float_mult150_gt0,
        #[MultipleOf(250.5), LessThan(0)]
        public float $float_mult2505_lt0,
        #[MultipleOf(-250), LessThan(0)]
        public int $int_mult_neg250_lt0,
        #[MultipleOf(5)]
        public int $int_mult5,
        #[MultipleOf(5), LessThan(3)]
        public int $int_mult5_lt3,
        #[Regex('/^[^\w]+$/')]
        public string $not_word,
        #[Regex('/^[^[:alnum:]]+$/')]
        public string $not_alnum_posix,
        #[Regex('/^[^a-zA-Z0-9]{3}$/')]
        public string $not_alnum,
        #[Regex('/^[^a-z]+$/')]
        public string $not_lower,
        #[Regex('/^[^0-9 ]+$/')]
        public string $not_digit_space,
        #[Regex('/^[^"\'<>]+$/')]
        public string $not_quotes,
        #[Regex('/^\d{4,}$/')]
        public string $digits_open,
        #[Regex('/^[a-z]{3,}$/')]
        public string $letters_open,
        #[Regex('/^[a-z]{2,}\d$/')]
        public string $letters_open_digit,
        #[Regex('/^[A-Z]{2,}$/'), Max(5)]
        public string $upper_open_max,
        #[StartsWith('ab'), Regex('/^[a-z]{3,}$/')]
        public string $starts_letters_open,
        #[Regex('/^(ab){2,}$/')]
        public string $group_open,
        #[Regex('/^[a-z]{0,}$/')]
        public string $letters_open_zero,
        #[Regex('/^(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/')]
        public string $password_lookahead,
        #[Regex('/^(?!admin)[a-z]{3,8}$/')]
        public string $not_admin,
        #[Regex('/(?i)^abc$/')]
        public string $inline_flag,
        #[Regex('/^\[[a-z]+\]$/')]
        public string $escaped_brackets,
        #[Regex('/^\W+$/')]
        public string $non_word,
        #[Regex('/^((ab)|c)d$/')]
        public string $nested_group,
        #[Regex('/^\x41{2}$/')]
        public string $hex_escape,
        #[Regex('/^\P{L}{3}$/u')]
        public string $not_letter_unicode,
        #[Regex('/^[A-Z]{2}\d{3,5}$/')]
        public string $closed_count,
        #[Regex('/^[a-z]+(-[a-z]+)?$/')]
        public string $optional_letters,
        #[Regex('/^(red|green|blue)$/')]
        public string $alternation,
        #[Regex('/^\d{3,}$/i')]
        public string $digits_open_i,
        #[Email, EndsWith('@corp.com')]
        public string $email_ends_domain,
        #[Email, Regex('/@corp\.com$/')]
        public string $email_regex_domain,
        #[Email, Regex('/@corp\.com$/i')]
        public string $email_regex_domain_i,
        #[Email, EndsWith('.org')]
        public string $email_ends_org,
        #[Email, StartsWith('a')]
        public string $email_starts_a,
        #[Url, EndsWith('.pdf'), Lowercase]
        public string $url_ends_pdf_lower,
        #[Url, Lowercase, Regex('/\.pdf$/i')]
        public string $url_lower_regex_pdf,
        #[IP, StartsWith('10.'), Lowercase]
        public string $ip_starts_10_lower,
        #[Uuid, StartsWith('A'), Uppercase]
        public string $uuid_starts_a_upper,
        #[Email, StartsWith('a'), Lowercase]
        public string $email_starts_a_lower,
        public array $strings,
        #[Min(5)]
        public array $strings_min5,
        #[Size(4)]
        public array $strings_size4,
        #[Max(2)]
        public array $strings_max2,
        #[Min(5)]
        public array $ints_min5,
        #[Min(4), Max(6)]
        public array $floats_between,
        #[Size(5)]
        public array $bools_size5,
        #[Min(4)]
        public array $enums_min4,
        #[Min(4), In(['a', 'b'])]
        public array $in_min4,
        #[Min(5)]
        public array $codes_min5,
        #[Size(4)]
        public array $codes_size4,
        #[Min(4)]
        public array $qtys_min4,
        public bool $plain_bool,
        public int $plain_int,
        public float $plain_float,
        #[Alpha]
        public string $plain_string,
        public ExampleGenerationRound7Colour $plain_enum,
        #[MultipleOf(5)]
        public string $string_mult5,
        #[MultipleOf(5), Digits(4)]
        public string $string_mult5_digits4,
        #[LessThan(3)]
        public string $string_lt3,
    ) {}
}
