<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\ExampleGenerationStage;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $faker = \Faker\Factory::create();
    $faker->seed(1234);
    $this->stage = new ExampleGenerationStage($faker);
    $this->context = function (string $type): ParameterContext {
        $property = app(DataConfig::class)->getDataClass(ExampleCoverageTestData::class)->properties->first();

        $context = new ParameterContext($property->name, $property);
        $context->type = $type;

        return $context;
    };
});

it('draws from the regexes it can read and skips one without a closing delimiter', function () {
    $context = ($this->context)('string');
    $context->valueRegexes = ['#unclosed', '/^ab+$/'];

    expect($this->stage->process($context)->example)->toMatch('/^ab+$/');
});

it('keeps escapes and classes whole when stripping the whitespace of an x-flag regex', function (string $regex) {
    foreach (range(1, 10) as $run) {
        $context = ($this->context)('string');
        $context->valueRegexes = [$regex];

        expect($this->stage->process($context)->example)->toMatch($regex);
    }
})->with([
    'escapes'   => ['/^ \d{3} \- \d{2} $/x'],
    'classes'   => ['/^ [a c]{2} z $/x'],
    'not utf-8' => ["/^[a-z]+ \xff? $/x"],
]);

it('falls back to a plain word when the pattern holds a construct the reader does not know', function (string $pattern) {
    $context = ($this->context)('string');
    $context->pattern = $pattern;

    expect($this->stage->process($context)->example)->toMatch('/^[a-z]+$/');
})->with([
    'reversed quantifier' => ['a{3,2}'],
    'unclosed class'      => ['[abc'],
    'not utf-8'           => ["\xff"],
    'unknown group'       => ['(a)(?1)'],
    'unclosed group'      => ['(abc'],
    'unknown escape'      => ['\q'],
    'undrawable class'    => ['^[\x{1F600}]$'],
]);

it('draws a control escape as its character', function () {
    $context = ($this->context)('string');
    $context->pattern = '^a\tb$';

    expect($this->stage->process($context)->example)->toBe("a\tb");
});

it('leaves a pattern draw unfitted when the length bounds cross', function () {
    $context = ($this->context)('string');
    $context->pattern = '^a+$';
    $context->minLength = 5;
    $context->maxLength = 3;

    expect($this->stage->process($context)->example)->toMatch('/^a+$/');
});

it('rejects a case copy of a date that no longer reads as its format', function () {
    $context = ($this->context)('string');
    $context->dateFormat = 'd M Y';
    $context->pattern = '^\d{2} [A-Z]{3} \d{4}$';

    expect($this->stage->process($context)->example)->toMatch('/^\d{2} [A-Z]{3} \d{4}$/');
});

it('keeps a numeric string example a multiple of both divisors', function () {
    foreach (range(1, 10) as $run) {
        $context = ($this->context)('string');
        $context->numericString = true;
        $context->multipleOf = 4;
        $context->exampleMultipleOf = 6;

        $example = $this->stage->process($context)->example;

        expect($example)->toBeNumeric()
            ->and((int) $example % 12)->toBe(0);
    }
});

it('still gives a numeric string when no form meets its pattern rules', function (?float $exclusiveMinimum, ?float $exclusiveMaximum) {
    $context = ($this->context)('string');
    $context->numericString = true;
    $context->exclusiveMinimum = $exclusiveMinimum;
    $context->exclusiveMaximum = $exclusiveMaximum;
    $context->valueRegexes = ['/^x$/'];

    expect($this->stage->process($context)->example)->toBeString()->toBeNumeric();
})->with([
    'whole range'   => [null, null],
    'decimal range' => [0.5, 0.9],
]);

it('gives the one value an inclusive range of width zero off the two-decimal grid allows', function () {
    $context = ($this->context)('number');
    $context->minimum = 0.001;
    $context->maximum = 0.001;

    expect($this->stage->process($context)->example)->toBe(0.001);
});

it('clamps an infinite exclusive minimum to the largest integer', function () {
    $context = ($this->context)('integer');
    $context->exclusiveMinimum = INF;

    expect($this->stage->process($context)->example)->toBe(PHP_INT_MAX);
});

it('ignores an example divisor no whole multiple of a thousand steps reaches', function () {
    $context = ($this->context)('integer');
    $context->exampleMultipleOf = 0.0001;

    expect($this->stage->process($context)->example)->toBeInt()->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(100);
});

it('ignores an example divisor with no multiple inside the bounds', function () {
    $context = ($this->context)('integer');
    $context->exampleMultipleOf = 7;
    $context->minimum = 1;
    $context->maximum = 6;

    expect($this->stage->process($context)->example)->toBeInt()->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(6);
});

class ExampleCoverageTestData extends Data
{
    public function __construct(
        public string $value,
    ) {}
}
