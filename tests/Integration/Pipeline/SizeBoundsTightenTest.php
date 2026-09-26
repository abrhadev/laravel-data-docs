<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\LessThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function boundsThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(SizeBoundsTightenTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

it('publishes the strictest bound whatever the declaration order', function (string $property, string $field, int $expected) {
    expect(boundsThroughPipeline($property)->$field)->toBe($expected);
})->with([
    'Min then GreaterThanOrEqualTo'              => ['min_then_gte', 'minimum', 5],
    'GreaterThanOrEqualTo then Min'              => ['gte_then_min', 'minimum', 5],
    'weaker Min then GreaterThanOrEqualTo'       => ['weak_min_then_gte', 'minimum', 5],
    'GreaterThanOrEqualTo then weaker Min'       => ['gte_then_weak_min', 'minimum', 5],
    'Max then LessThanOrEqualTo'                 => ['max_then_lte', 'maximum', 10],
    'LessThanOrEqualTo then Max'                 => ['lte_then_max', 'maximum', 10],
    'Between then GreaterThanOrEqualTo, minimum' => ['between_then_gte', 'minimum', 4],
    'Between then GreaterThanOrEqualTo, maximum' => ['between_then_gte', 'maximum', 8],
    'GreaterThanOrEqualTo then Between, minimum' => ['gte_then_between', 'minimum', 4],
    'GreaterThanOrEqualTo then Between, maximum' => ['gte_then_between', 'maximum', 8],
    'Size then Min on a string'                  => ['size_then_min', 'minLength', 5],
    'Min then Size on a string'                  => ['min_then_size', 'minLength', 5],
]);

it('keeps a literal bound when a field reference names the same side', function (string $property) {
    $context = boundsThroughPipeline($property);

    expect($context->minimum)->toBe(5)
        ->and($context->descriptions)->toContain('Must be greater than or equal to <b><i>floor</i></b>.');
})->with(['min_then_reference', 'reference_then_min']);

it('gives an example that meets a fractional length or item-count bound', function (string $property, string $field, int $bound) {
    $rules = SizeBoundsTightenTestData::getValidationRules([])[$property];

    foreach (range(1, 20) as $run) {
        $context = boundsThroughPipeline($property);

        expect($context->$field)->toBe($bound)
            ->and(Validator::make([$property => $context->example], [$property => $rules])->passes())->toBeTrue();
    }
})->with([
    'string Min'     => ['fraction_length_min', 'minLength', 3],
    'string Max'     => ['fraction_length_max', 'maxLength', 2],
    'string Between' => ['fraction_length_between', 'minLength', 2],
    'array Min'      => ['fraction_items_min', 'minItems', 2],
    'array Max'      => ['fraction_items_max', 'maxItems', 2],
]);

it('gives a long string example that meets a large minimum length', function (string $property) {
    $rules = SizeBoundsTightenTestData::getValidationRules([])[$property];

    foreach (range(1, 100) as $run) {
        $example = boundsThroughPipeline($property)->example;

        // Laravel's TrimStrings middleware trims the value first, so it is also checked trimmed.
        expect(Validator::make([$property => trim($example)], [$property => $rules])->passes())->toBeTrue(strlen(trim($example)) . ' characters once trimmed');
    }
})->with(['long_min', 'longer_min', 'long_min_with_max', 'long_exact']);

it('pins that Laravel reads a fractional length bound as a whole number', function (mixed $value, string $rule, bool $passes) {
    expect(Validator::make(['a' => $value], ['a' => $rule])->passes())->toBe($passes);
})->with([
    'min:2.5 rejects 2 characters'  => ['ab', 'string|min:2.5', false],
    'min:2.5 accepts 3 characters'  => ['abc', 'string|min:2.5', true],
    'max:2.5 accepts 2 characters'  => ['ab', 'string|max:2.5', true],
    'max:2.5 rejects 3 characters'  => ['abc', 'string|max:2.5', false],
    'min:2.5 rejects 2 items'       => [[1, 2], 'array|min:2.5', false],
    'max:2.5 rejects 3 items'       => [[1, 2, 3], 'array|max:2.5', false],
    'size:2.5 rejects 2 characters' => ['ab', 'string|size:2.5', false],
    'size:2.5 rejects 3 characters' => ['abc', 'string|size:2.5', false],
]);

it('publishes an impossible fractional size as a note and no bound', function () {
    $context = boundsThroughPipeline('impossible_size');

    expect($context->description)->toContain('Note: must have exactly <code>2.5</code> characters, which no string can have, so any request that sends this field fails validation.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKeys(['minLength', 'maxLength']);
});

it('publishes a reversed range on a number field as a note and no bound', function () {
    $context = boundsThroughPipeline('reversed_number_between');

    expect($context->description)->toContain('Note: must be between <code>5</code> and <code>2</code>, which no number can be, so any request that sends this field fails validation.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKeys(['minimum', 'maximum']);
});

it('publishes a range with no whole number as a note and no bound', function () {
    $context = boundsThroughPipeline('impossible_between');

    expect($context->description)->toContain('Note: must be between <code>2.2</code> and <code>2.8</code>, which no integer can be, so any integer sent fails validation.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKeys(['minimum', 'maximum']);
});

it('pins the rules Laravel Data gives an int property with a range no integer is in', function () {
    // Spatie infers numeric, not integer, for an int: every integer fails, a fraction passes.
    $rules = SizeBoundsTightenTestData::getValidationRules([])['impossible_between'];

    expect($rules)->toContain('numeric')->not->toContain('integer')
        ->and(Validator::make(['v' => 2], ['v' => $rules])->passes())->toBeFalse()
        ->and(Validator::make(['v' => 3], ['v' => $rules])->passes())->toBeFalse()
        ->and(Validator::make(['v' => 2.5], ['v' => $rules])->passes())->toBeTrue();
});

it('pins that a range with no whole number rejects every integer, string and array', function (mixed $value, string $rule, bool $passes) {
    expect(Validator::make(['a' => $value], ['a' => $rule])->passes())->toBe($passes);
})->with([
    'integer 2'                     => [2, 'integer|between:2.2,2.8', false],
    'integer 3'                     => [3, 'integer|between:2.2,2.8', false],
    'string of 3'                   => ['abc', 'string|between:2.2,2.8', false],
    'array of 2'                    => [[1, 2], 'array|between:2.2,2.8', false],
    'reversed range'                => ['abc', 'string|between:5,2', false],
    'number 2.5'                    => [2.5, 'numeric|between:2.2,2.8', true],
    'reversed on a number'          => [3.5, 'numeric|between:5,2', false],
    'a one-value range on a number' => [2.5, 'numeric|between:2.5,2.5', true],
    'a whole number inside'         => [3, 'integer|between:2.5,3.5', true],
]);

it('publishes a negative length or item count as a note and no bound', function (string $property, string $note) {
    $context = boundsThroughPipeline($property);

    expect($context->description)->toContain($note)
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKeys(['minLength', 'maxLength', 'minItems', 'maxItems']);
})->with([
    'negative size on a string'  => ['negative_size', 'Note: must have exactly <code>-1</code> characters, which no string can have, so any request that sends this field fails validation.'],
    'negative range on an array' => ['negative_between', 'Note: must have between <code>-5</code> and <code>-2</code> items, which no array can have, so any request that sends this field fails validation.'],
]);

it('gives an example that is a multiple of a divisor multipleOf cannot publish', function (string $property) {
    $rules = SizeBoundsTightenTestData::getValidationRules([])[$property];

    foreach (range(1, 30) as $run) {
        $context = boundsThroughPipeline($property);

        // Checked as the PHP value and as the JSON a client sends.
        expect($context->toParameter()->openApiAttributes)->not->toHaveKey('multipleOf')
            ->and(Validator::make([$property => $context->example], [$property => $rules])->passes())->toBeTrue("{$property}: " . var_export($context->example, true))
            ->and(Validator::make([$property => json_decode(json_encode($context->example))], [$property => $rules])->passes())->toBeTrue("{$property}: " . json_encode($context->example));
    }
})->with(['multiple_of_tenth', 'multiple_of_quarter', 'multiple_of_half_int', 'multiple_of_negative', 'multiple_of_negative_float', 'multiple_of_bounded', 'multiple_of_quarter_below_zero', 'multiple_of_negative_below_zero', 'multiple_below_divisor_float', 'multiple_large_below_two', 'multiple_negative_below_two']);

it('gives a published multipleOf a multiple when the upper bound is below the divisor', function (string $property) {
    $rules = SizeBoundsTightenTestData::getValidationRules([])[$property];

    foreach (range(1, 30) as $run) {
        $example = boundsThroughPipeline($property)->example;

        expect(Validator::make(['v' => $example], ['v' => $rules])->passes())->toBeTrue(var_export($example, true));
    }
})->with(['published_multiple_below', 'published_multiple_max_float']);

it('gives a multiple below zero when only an upper bound below 1 is declared', function () {
    $rules = SizeBoundsTightenTestData::getValidationRules([])['multiple_of_three_below_zero'];

    foreach (range(1, 30) as $run) {
        $example = boundsThroughPipeline('multiple_of_three_below_zero')->example;

        expect(Validator::make(['v' => $example], ['v' => $rules])->passes())->toBeTrue(var_export($example, true));
    }
});

it('states MultipleOf on a string field as a numeric rule and gives a numeric example that is a multiple', function (string $property) {
    $rules = SizeBoundsTightenTestData::getValidationRules([])[$property];
    $context = boundsThroughPipeline($property);

    // multiple_of rejects any value that is not numeric, and JSON Schema ignores multipleOf on a string.
    expect($context->description)->toContain('Must be a number that is a multiple of')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('multipleOf');

    foreach (range(1, 30) as $run) {
        $example = boundsThroughPipeline($property)->example;

        expect(Validator::make([$property => $example], [$property => $rules])->passes())->toBeTrue(var_export($example, true));
    }
})->with(['multiple_of_string', 'multiple_of_string_bounded', 'multiple_of_string_fraction', 'digits_multiple_string', 'digits_four_multiple_string', 'regex_multiple_string', 'fraction_multiple_max_string', 'multiple_max_one_string', 'multiple_max_two_string']);

it('publishes MultipleOf(0) as a note', function () {
    $context = boundsThroughPipeline('multiple_of_zero');

    // A zero multipleOf is invalid OpenAPI.
    expect($context->description)
        ->toContain('Note: must be a multiple of 0, which no value can be, so any request that sends this field fails validation.')
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('multipleOf');
});

it('pins that a negative length and a zero divisor reject every value', function (mixed $value, string $rule) {
    expect(Validator::make(['a' => $value], ['a' => $rule])->passes())->toBeFalse();
})->with([
    'string, size -1'      => ['a', 'string|size:-1'],
    'array, between -5,-2' => [[1], 'array|between:-5,-2'],
    'empty array, between' => [[], 'present|array|between:-5,-2'],
    'zero, multiple_of 0'  => [0, 'integer|multiple_of:0'],
    'six, multiple_of 0'   => [6, 'integer|multiple_of:0'],
]);

it('pins that a fractional size rejects every integer but not a number', function (mixed $value, string $rule, bool $passes) {
    expect(Validator::make(['a' => $value], ['a' => $rule])->passes())->toBe($passes);
})->with([
    'integer 2'  => [2, 'integer|size:2.5', false],
    'integer 3'  => [3, 'integer|size:2.5', false],
    'number 2.5' => [2.5, 'numeric|size:2.5', true],
]);

it('publishes a fractional comparison bound exactly', function () {
    $context = boundsThroughPipeline('fractional');

    expect($context->toParameter()->openApiAttributes['exclusiveMinimum'])->toBe(0.5)
        ->and($context->description)->toContain('Must be greater than <code>0.5</code>.');
});

class SizeBoundsTightenTestData extends Data
{
    public function __construct(
        #[Min(5), GreaterThanOrEqualTo(3)]
        public int $min_then_gte,
        #[GreaterThanOrEqualTo(3), Min(5)]
        public int $gte_then_min,
        #[Min(3), GreaterThanOrEqualTo(5)]
        public int $weak_min_then_gte,
        #[GreaterThanOrEqualTo(5), Min(3)]
        public int $gte_then_weak_min,
        #[Max(10), LessThanOrEqualTo(20)]
        public int $max_then_lte,
        #[LessThanOrEqualTo(20), Max(10)]
        public int $lte_then_max,
        #[Between(2, 8), GreaterThanOrEqualTo(4)]
        public int $between_then_gte,
        #[GreaterThanOrEqualTo(4), Between(2, 8)]
        public int $gte_then_between,
        #[Size(5), Min(3)]
        public string $size_then_min,
        #[Min(3), Size(5)]
        public string $min_then_size,
        #[Min(5), GreaterThanOrEqualTo('floor')]
        public int $min_then_reference,
        #[GreaterThanOrEqualTo('floor'), Min(5)]
        public int $reference_then_min,
        #[GreaterThan(0.5)]
        public float $fractional,
        #[Size(2.5)]
        public string $impossible_size,
        #[Size(-1)]
        public string $negative_size,
        #[Min(40)]
        public string $long_min,
        #[Min(200)]
        public string $longer_min,
        #[Min(200), Max(210)]
        public string $long_min_with_max,
        /** @var string[] */
        #[Between(-5, -2)]
        public array $negative_between,
        #[MultipleOf(0)]
        public int $multiple_of_zero,
        #[MultipleOf(5)]
        public string $multiple_of_string,
        #[Digits(3), MultipleOf(3)]
        public string $digits_multiple_string,
        #[MultipleOf(5), Digits(4)]
        public string $digits_four_multiple_string,
        #[MultipleOf(3), Regex('/^\d+$/'), Min(3)]
        public string $regex_multiple_string,
        #[MultipleOf(0.7), Max(3)]
        public string $fraction_multiple_max_string,
        #[MultipleOf(3), Max(1)]
        public string $multiple_max_one_string,
        #[MultipleOf(25), Max(2)]
        public string $multiple_max_two_string,
        #[MultipleOf(0.3), LessThan(1.1)]
        public float $multiple_below_divisor_float,
        #[MultipleOf(2.5), LessThan(2)]
        public float $multiple_large_below_two,
        #[MultipleOf(-3), LessThan(2)]
        public int $multiple_negative_below_two,
        #[MultipleOf(5), LessThan(3)]
        public int $published_multiple_below,
        #[MultipleOf(5), Max(3)]
        public float $published_multiple_max_float,
        #[GreaterThan(0), MultipleOf(7)]
        public string $multiple_of_string_bounded,
        #[MultipleOf(0.5)]
        public string $multiple_of_string_fraction,
        #[Min(15), Max(15)]
        public string $long_exact,
        #[MultipleOf(0.1)]
        public float $multiple_of_tenth,
        #[MultipleOf(0.25)]
        public float $multiple_of_quarter,
        #[MultipleOf(0.5)]
        public int $multiple_of_half_int,
        #[MultipleOf(-3)]
        public int $multiple_of_negative,
        #[MultipleOf(-0.2)]
        public float $multiple_of_negative_float,
        #[MultipleOf(0.3), Between(1, 2)]
        public float $multiple_of_bounded,
        #[MultipleOf(0.25), LessThan(0)]
        public float $multiple_of_quarter_below_zero,
        #[MultipleOf(-3), LessThan(0)]
        public int $multiple_of_negative_below_zero,
        #[MultipleOf(3), LessThan(0)]
        public int $multiple_of_three_below_zero,
        #[Between(2.2, 2.8)]
        public int $impossible_between,
        #[Between(5, 2)]
        public float $reversed_number_between,
        #[Min(2.5)]
        public string $fraction_length_min,
        #[Max(2.5)]
        public string $fraction_length_max,
        #[Between(1.5, 2.5)]
        public string $fraction_length_between,
        /** @var string[] */
        #[Min(1.5)]
        public array $fraction_items_min,
        /** @var string[] */
        #[Max(2.5)]
        public array $fraction_items_max,
        public int $floor = 0,
    ) {}
}
