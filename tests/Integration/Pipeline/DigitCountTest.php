<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function digitCountThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(DigitCountTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function digitCountPasses(string $property, mixed $value): bool
{
    $rules = DigitCountTestData::getValidationRules([])[$property];

    return Validator::make([$property => $value], [$property => $rules])->passes();
}

it('publishes a digit count on a string as a pattern and a length, not a range', function (string $property, array $expected) {
    $attributes = digitCountThroughPipeline($property)->toParameter()->openApiAttributes;

    expect(array_intersect_key($attributes, $expected))->toBe($expected)
        ->and($attributes)->not->toHaveKeys(['minimum', 'maximum']);
})->with([
    'Digits'        => ['pin', ['pattern' => '^[0-9]{4}$', 'minLength' => 4, 'maxLength' => 4]],
    'DigitsBetween' => ['account', ['pattern' => '^[0-9]{2,8}$', 'minLength' => 2, 'maxLength' => 8]],
]);

it('publishes a digit count on an integer or a number as a whole-number range', function (string $property, array $expected) {
    $attributes = digitCountThroughPipeline($property)->toParameter()->openApiAttributes;

    expect(array_intersect_key($attributes, $expected))->toBe($expected)
        ->and($attributes)->not->toHaveKeys(['pattern', 'minLength', 'maxLength']);
})->with([
    'three digits'         => ['code', ['minimum' => 100, 'maximum' => 999]],
    'one digit includes 0' => ['single', ['minimum' => 0, 'maximum' => 9]],
    'nineteen digits'      => ['long', ['minimum' => 1000000000000000000]],
    'a float'              => ['amount', ['minimum' => 100, 'maximum' => 999, 'multipleOf' => 1]],
    'a float digit range'  => ['amount_range', ['minimum' => 10, 'maximum' => 9999, 'multipleOf' => 1]],
    'Digits then Min'      => ['digits_then_min', ['minimum' => 500, 'maximum' => 999]],
    'Min then Digits'      => ['min_then_digits', ['minimum' => 500, 'maximum' => 999]],
]);

it('publishes no maximum an int cannot hold', function () {
    expect(digitCountThroughPipeline('long')->toParameter()->openApiAttributes)->not->toHaveKey('maximum');
});

it('tightens a length bound in either declaration order', function (string $property) {
    $attributes = digitCountThroughPipeline($property)->toParameter()->openApiAttributes;

    expect($attributes['minLength'])->toBe(5)
        ->and($attributes['maxLength'])->toBe(8);
})->with(['min_then_between', 'between_then_min']);

it('narrows the digit pattern to a length declared before it', function () {
    expect(digitCountThroughPipeline('min_then_between')->pattern)->toBe('^[0-9]{5,8}$');
});

it('gives an example that passes the rule', function (string $property) {
    foreach (range(1, 25) as $run) {
        $example = digitCountThroughPipeline($property)->example;

        // Checked as the PHP value and as the JSON a client sends.
        expect(digitCountPasses($property, $example))->toBeTrue("{$property}: " . var_export($example, true))
            ->and(digitCountPasses($property, json_decode(json_encode($example))))->toBeTrue("{$property}: " . json_encode($example));
    }
})->with([
    'pin', 'account', 'code', 'single', 'long', 'amount', 'amount_range', 'amount_fifteen', 'amount_long', 'amount_longer', 'amount_half_multiple', 'multiple_then_digits', 'amount_negative_multiple',
    'digits_then_min', 'min_then_digits', 'min_then_between',
]);

it('gives a string with a divisor an example that passes the rule and the published pattern', function (string $property) {
    foreach (range(1, 40) as $run) {
        $context = digitCountThroughPipeline($property);
        $pattern = $context->toParameter()->openApiAttributes['pattern'];

        // MultipleOf makes the string numeric: a whole multiple padded with leading zeros, never a decimal the digit rule rejects.
        expect(digitCountPasses($property, $context->example))->toBeTrue("{$property}: " . var_export($context->example, true))
            ->and(preg_match("\x01{$pattern}\x01u", $context->example))->toBe(1, "{$property}: {$context->example} against {$pattern}");
    }
})->with(['code_whole_multiple', 'code_half_multiple', 'code_four_half_multiple', 'code_range_multiple', 'code_small_range_multiple']);

it('publishes a reversed digit range as a note and no constraint', function (string $property) {
    $context = digitCountThroughPipeline($property);

    expect($context->description)->toContain('Note: must have between <code>5</code> and <code>2</code> digits, which no value can have, so any request that sends this field fails validation.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKeys(['pattern', 'minLength', 'maxLength', 'minimum', 'maximum', 'multipleOf']);
})->with(['reversed', 'reversed_integer']);

it('pins how Laravel counts digits', function (string $property, mixed $value, bool $passes) {
    expect(digitCountPasses($property, $value))->toBe($passes);
})->with([
    'a string keeps its leading zeros'  => ['pin', '0070', true],
    'a string of four digits'           => ['pin', '1234', true],
    'a string that is too short'        => ['pin', '123', false],
    'a negative string'                 => ['pin', '-123', false],
    'a decimal string'                  => ['pin', '12.5', false],
    'an exponent string'                => ['pin', '12e3', false],
    'a trailing newline'                => ["pin", "1234\n", false],
    'an integer on a string property'   => ['pin', 1234, false],
    'a string range, leading zeros'     => ['account', '01', true],
    'a string range, too long'          => ['account', '123456789', false],
    'an integer of three digits'        => ['code', 123, true],
    'an integer of two digits'          => ['code', 99, false],
    'an integer of four digits'         => ['code', 1000, false],
    'a negative integer'                => ['code', -123, false],
    'zero has one digit'                => ['single', 0, true],
    'nine has one digit'                => ['single', 9, true],
    'ten has two digits'                => ['single', 10, false],
    'a whole float'                     => ['amount', 123.0, true],
    'a fractional float'                => ['amount', 123.5, false],
    'the largest int has 19 digits'     => ['long', PHP_INT_MAX, true],
    'the smallest 19-digit int'         => ['long', 1000000000000000000, true],
    'a number beyond the int range'     => ['long', 9999999999999999999, false],
    'a reversed range rejects 2 digits' => ['reversed', '12', false],
    'a reversed range rejects 3 digits' => ['reversed', '123', false],
    'a reversed range rejects 5 digits' => ['reversed', '12345', false],
    'a reversed range rejects integers' => ['reversed_integer', 123, false],
]);

class DigitCountTestData extends Data
{
    public function __construct(
        #[Digits(4)]
        public string $pin,
        #[DigitsBetween(2, 8)]
        public string $account,
        #[Digits(3)]
        public int $code,
        #[Digits(1)]
        public int $single,
        #[Digits(19)]
        public int $long,
        #[Digits(3)]
        public float $amount,
        #[DigitsBetween(2, 4)]
        public float $amount_range,
        #[Digits(15)]
        public float $amount_fifteen,
        #[Digits(18)]
        public float $amount_long,
        #[Digits(19)]
        public float $amount_longer,
        #[Digits(3), MultipleOf(1.5)]
        public float $amount_half_multiple,
        #[MultipleOf(2.5), Digits(3)]
        public float $multiple_then_digits,
        #[Digits(3), MultipleOf(-3)]
        public float $amount_negative_multiple,
        #[Digits(3), Min(500)]
        public int $digits_then_min,
        #[Min(500), Digits(3)]
        public int $min_then_digits,
        #[Min(5), DigitsBetween(2, 8)]
        public string $min_then_between,
        #[DigitsBetween(2, 8), Min(5)]
        public string $between_then_min,
        #[Digits(3), MultipleOf(5)]
        public string $code_whole_multiple,
        #[Digits(3), MultipleOf(1.5)]
        public string $code_half_multiple,
        #[MultipleOf(0.5), Digits(4)]
        public string $code_four_half_multiple,
        #[DigitsBetween(2, 4), MultipleOf(2.5)]
        public string $code_range_multiple,
        #[MultipleOf(1.5), DigitsBetween(1, 3)]
        public string $code_small_range_multiple,
        #[DigitsBetween(5, 2)]
        public string $reversed,
        #[DigitsBetween(5, 2)]
        public int $reversed_integer,
    ) {}
}
