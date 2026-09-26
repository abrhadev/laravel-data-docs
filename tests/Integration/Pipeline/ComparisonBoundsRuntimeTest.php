<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\LessThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function comparisonThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ComparisonBoundsRuntimeTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function publishedNumericBounds(ParameterContext $context): array
{
    return array_intersect_key(
        $context->toParameter()->openApiAttributes,
        array_flip(['minimum', 'maximum', 'exclusiveMinimum', 'exclusiveMaximum'])
    );
}

it('documents a comparison by what the validator compares for the field type', function (string $property, string $sentence) {
    expect(comparisonThroughPipeline($property)->description)->toContain($sentence);
})->with([
    'integer, literal'     => ['count_gt', 'Must be greater than <code>5</code>.'],
    'string, literal gt'   => ['code_gt', 'Must be a number greater than <code>5</code>.'],
    'string, literal gte'  => ['code_gte', 'Must be a number greater than or equal to <code>5</code>.'],
    'string, literal lt'   => ['code_lt', 'Must be a number less than <code>5</code>.'],
    'string, literal lte'  => ['code_lte', 'Must be a number less than or equal to <code>5</code>.'],
    'string, reference'    => ['nickname', 'Must be greater than <b><i>name</i></b>, compared as numbers when both values are numeric and by length otherwise.'],
    'array, literal'       => ['tags_gt', 'Note: must be greater than <code>5</code>, which no array can be, so any request that sends this field fails validation.'],
    'array, reference gt'  => ['picked_gt', 'Must have more items than <b><i>options</i></b>.'],
    'array, reference gte' => ['picked_gte', 'Must have at least as many items as <b><i>options</i></b>.'],
    'array, reference lt'  => ['picked_lt', 'Must have fewer items than <b><i>options</i></b>.'],
    'array, reference lte' => ['picked_lte', 'Must have at most as many items as <b><i>options</i></b>.'],
]);

it('publishes a numeric schema bound only on a numeric field', function () {
    expect(publishedNumericBounds(comparisonThroughPipeline('count_gt')))->toBe(['exclusiveMinimum' => 5]);

    foreach (['code_gt', 'code_gte', 'code_lt', 'code_lte', 'nickname', 'tags_gt', 'picked_gt', 'picked_lte'] as $property) {
        expect(publishedNumericBounds(comparisonThroughPipeline($property)))->toBe([]);
    }
});

it('gives a string field with a literal bound a numeric example that passes it', function (string $property, string $rule) {
    foreach (range(1, 20) as $run) {
        $example = comparisonThroughPipeline($property)->example;

        expect($example)->toBeString()->toBeNumeric()
            ->and(Validator::make(['a' => $example], ['a' => "string|{$rule}"])->passes())->toBeTrue();
    }
})->with([
    'gt'                     => ['code_gt', 'gt:5'],
    'gte'                    => ['code_gte', 'gte:5'],
    'lt'                     => ['code_lt', 'lt:5'],
    'lte'                    => ['code_lte', 'lte:5'],
    'gt and lt on one field' => ['code_between', 'gt:10|lt:20'],
]);

it('gives a numeric example that also meets the length bounds', function (string $property) {
    $rules = ComparisonBoundsRuntimeTestData::getValidationRules([])[$property];

    foreach (range(1, 20) as $run) {
        $example = comparisonThroughPipeline($property)->example;

        expect($example)->toBeString()->toBeNumeric()
            ->and(Validator::make([$property => $example], [$property => $rules])->passes())->toBeTrue();
    }
})->with([
    'GreaterThan then Min'                   => 'length_gt_min',
    'Min then GreaterThan'                   => 'length_min_gt',
    'a range too small for Min, padded'      => 'length_pad_lt_min',
    'Max caps the digits'                    => 'length_max_gt',
    'Between bounds the length'              => 'length_gt_between',
    'a negative value padded to its Size'    => 'length_size_negative',
    'the lower bound already has the length' => 'length_gte_max',
]);

it('gives an example that meets a fractional bound', function (string $property) {
    $rules = ComparisonBoundsRuntimeTestData::getValidationRules([])[$property];

    foreach (range(1, 20) as $run) {
        $context = comparisonThroughPipeline($property);

        expect(Validator::make([$property => $context->example], [$property => $rules])->passes())->toBeTrue();
    }
})->with([
    'integer below a fractional bound'             => 'fraction_int_lt',
    'integer between two fractional bounds'        => 'fraction_int_between',
    'number between two fractional bounds'         => 'fraction_number_between',
    'number within three-decimal bounds'           => 'fraction_number_narrow',
    'number with a fractional Min and Max'         => 'fraction_number_min_max',
    'numeric string between two fractional bounds' => 'fraction_code_between',
    'numeric string with no whole number inside'   => 'fraction_code_no_whole',
    'numeric string, narrow and inclusive'         => 'fraction_code_inclusive',
    'numeric string with no whole number, Min'     => 'fraction_code_min',
    'numeric string with no whole number, Max'     => 'fraction_code_max',
]);

it('publishes fractional numeric bounds exactly', function (string $property, array $bounds) {
    expect(publishedNumericBounds(comparisonThroughPipeline($property)))->toBe($bounds);
})->with([
    'integer'         => ['fraction_int_lt', ['exclusiveMaximum' => 0.5]],
    'number'          => ['fraction_number_narrow', ['minimum' => 0.125, 'maximum' => 0.135]],
    'number, Min/Max' => ['fraction_number_min_max', ['minimum' => 0.25, 'maximum' => 0.75]],
    'numeric string'  => ['fraction_code_between', []],
]);

it('pins that Min bounds the length of a numeric string in either order', function (string $rules) {
    expect(Validator::make(['a' => '123'], ['a' => $rules])->passes())->toBeFalse()
        ->and(Validator::make(['a' => '0007'], ['a' => $rules])->passes())->toBeTrue();
})->with(['string|gt:5|min:4', 'string|min:4|gt:5']);

it('keeps a word example on a string field compared with a reference', function () {
    expect(comparisonThroughPipeline('nickname')->example)->toBeString()->not->toBeNumeric();
});

it('pins the runtime behaviour the sentences describe', function (array $data, string $rule, bool $passes) {
    expect(Validator::make($data, ['a' => $rule])->passes())->toBe($passes);
})->with([
    'a numeric string is compared as a number'    => [['a' => '9'], 'string|gt:5', true],
    'a numeric string below the bound passes'     => [['a' => '1'], 'string|lt:5', true],
    'a non-numeric string never passes a literal' => [['a' => 'abcdef'], 'string|gt:5', false],
    'nor does a short one'                        => [['a' => 'abc'], 'string|lt:5', false],
    'an array never passes a literal'             => [['a' => [1, 2, 3, 4, 5, 6]], 'array|gt:5', false],
    'nor does the empty array'                    => [['a' => []], 'array|lte:5', false],
    'a reference compares string length'          => [['a' => 'abcdef', 'b' => 'abc'], 'string|gt:b', true],
    'a shorter string fails'                      => [['a' => 'ab', 'b' => 'abc'], 'string|gt:b', false],
    'numeric strings compare as numbers'          => [['a' => '10', 'b' => '9'], 'string|gt:b', true],
    'a reference compares item count'             => [['a' => [1, 2, 3], 'b' => [1]], 'array|gt:b', true],
    'fewer items fail'                            => [['a' => [1], 'b' => [1, 2]], 'array|gt:b', false],
]);

it('gives a float example inside a narrow range', function (string $property) {
    $rules = ComparisonBoundsRuntimeTestData::getValidationRules([])[$property];

    foreach (range(1, 25) as $run) {
        $example = comparisonThroughPipeline($property)->example;

        expect(Validator::make([$property => $example], [$property => $rules])->passes())->toBeTrue("{$example} failed");
    }
})->with([
    'exclusive'                       => 'narrow',
    'one two-decimal value inside'    => 'narrow_grid',
    'inclusive, no two-decimal value' => 'narrow_inclusive',
    'exclusive, no two-decimal value' => 'narrow_off_grid',
]);

class ComparisonBoundsRuntimeTestData extends Data
{
    /**
     * @param string[] $tags_gt
     * @param string[] $picked_gt
     * @param string[] $picked_gte
     * @param string[] $picked_lt
     * @param string[] $picked_lte
     * @param string[] $options
     */
    public function __construct(
        #[GreaterThan(0.5), LessThan(0.55)]
        public float $narrow,
        #[GreaterThan(0.555), LessThan(0.565)]
        public float $narrow_grid,
        #[GreaterThanOrEqualTo(0.005), LessThanOrEqualTo(0.009)]
        public float $narrow_inclusive,
        #[GreaterThan(0.001), LessThan(0.009)]
        public float $narrow_off_grid,
        #[GreaterThan(5)]
        public int $count_gt,
        #[GreaterThan(5)]
        public string $code_gt,
        #[GreaterThanOrEqualTo(5)]
        public string $code_gte,
        #[LessThan(5)]
        public string $code_lt,
        #[LessThanOrEqualTo(5)]
        public string $code_lte,
        #[GreaterThan(10), LessThan(20)]
        public string $code_between,
        #[GreaterThan(5), Min(4)]
        public string $length_gt_min,
        #[Min(4), GreaterThan(5)]
        public string $length_min_gt,
        #[LessThan(50), Min(4)]
        public string $length_pad_lt_min,
        #[Max(2), GreaterThan(90)]
        public string $length_max_gt,
        #[GreaterThan(5), Between(3, 4)]
        public string $length_gt_between,
        #[Size(6), LessThan(0)]
        public string $length_size_negative,
        #[GreaterThanOrEqualTo(1000), Max(4)]
        public string $length_gte_max,
        #[LessThan(0.5)]
        public int $fraction_int_lt,
        #[GreaterThan(2.5), LessThan(3.9)]
        public int $fraction_int_between,
        #[GreaterThan(0.5), LessThan(0.9)]
        public float $fraction_number_between,
        #[GreaterThanOrEqualTo(0.125), LessThanOrEqualTo(0.135)]
        public float $fraction_number_narrow,
        #[Min(0.25), Max(0.75)]
        public float $fraction_number_min_max,
        #[GreaterThan(0.5), LessThan(3.5)]
        public string $fraction_code_between,
        #[GreaterThan(0.5), LessThan(0.9)]
        public string $fraction_code_no_whole,
        #[GreaterThanOrEqualTo(0.005), LessThanOrEqualTo(0.009)]
        public string $fraction_code_inclusive,
        #[GreaterThan(0.5), LessThan(0.9), Min(6)]
        public string $fraction_code_min,
        #[GreaterThan(0.5), LessThan(0.9), Max(3)]
        public string $fraction_code_max,
        #[GreaterThan('name')]
        public string $nickname,
        public string $name,
        #[GreaterThan(5)]
        public array $tags_gt,
        #[GreaterThan('options')]
        public array $picked_gt,
        #[GreaterThanOrEqualTo('options')]
        public array $picked_gte,
        #[LessThan('options')]
        public array $picked_lt,
        #[LessThanOrEqualTo('options')]
        public array $picked_lte,
        public array $options,
    ) {}
}
