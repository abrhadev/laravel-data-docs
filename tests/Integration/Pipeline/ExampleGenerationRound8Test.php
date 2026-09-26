<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function round8ThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ExampleGenerationRound8TestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

/**
 * Builds the property many times; every example must pass the Data class's
 * own rules, as the PHP value and as the JSON a client sends.
 */
function round8ExpectPassingExamples(string $property, int $runs = 40): void
{
    $rules = ExampleGenerationRound8TestData::getValidationRules([])[$property];

    foreach (range(1, $runs) as $run) {
        $context = round8ThroughPipeline($property);
        $example = $context->example;
        $shown = "{$property}: " . var_export($example, true);

        expect(Validator::make([$property => $example], [$property => $rules])->passes())->toBeTrue($shown)
            ->and(Validator::make([$property => json_decode(json_encode($example))], [$property => $rules])->passes())->toBeTrue($shown . ' after JSON');
    }
}

it('repeats a backreference\'s own group instead of drawing it empty', function (string $property) {
    round8ExpectPassingExamples($property);
})->with(['backreference', 'nested_backreference']);

it('strips an x-flagged regex\'s insignificant whitespace and comments before drawing from it', function (string $property) {
    round8ExpectPassingExamples($property);
})->with(['extended_whitespace', 'extended_comment']);

it('keeps a numeric-string multiple strictly beyond a bound near PHP_INT_MAX', function () {
    round8ExpectPassingExamples('mult3_gt_near_int_max');
});

class ExampleGenerationRound8TestData extends Data
{
    public function __construct(
        #[Regex('/^(\w)\1{3}$/')]
        public string $backreference,
        #[Regex('/^((ab)\2){2}$/')]
        public string $nested_backreference,
        #[Regex('/^a b$/x')]
        public string $extended_whitespace,
        #[Regex("/^a # a comment\n b$/x")]
        public string $extended_comment,
        #[MultipleOf(3), GreaterThan(9.2e18)]
        public string $mult3_gt_near_int_max,
    ) {}
}
