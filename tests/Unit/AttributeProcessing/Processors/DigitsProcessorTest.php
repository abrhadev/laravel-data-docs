<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsBetweenProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new DigitsProcessor();
});

it('documents a digit count on a string as a pattern and a length', function () {
    $testData = new class ('1234') extends Data {
        public function __construct(
            #[Digits(4)]
            public string $pinCode,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'pinCode');

    $context = new ParameterContext('pinCode', $property);
    $context->type = 'string';

    $attribute = new Digits(4);
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('^[0-9]{4}$')
        ->and($context->minLength)->toBe(4)
        ->and($context->maxLength)->toBe(4)
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull()
        ->and($context->descriptions)->toContain('Must have exactly <code>4</code> digits.');
});

it('documents a single digit on a string as a one-character pattern', function () {
    $testData = new class ('5') extends Data {
        public function __construct(
            #[Digits(1)]
            public string $digit,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'digit');

    $context = new ParameterContext('digit', $property);
    $context->type = 'string';

    $attribute = new Digits(1);
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('^[0-9]{1}$')
        ->and($context->minLength)->toBe(1)
        ->and($context->maxLength)->toBe(1)
        ->and($context->minimum)->toBeNull();
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new Digits(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('skips zero digits it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new Digits(0), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('documents a digit count on an integer as the range of whole numbers', function (int $digits, int $minimum, int $maximum) {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new Digits($digits), $context);

    expect($context->minimum)->toBe($minimum)
        ->and($context->maximum)->toBe($maximum)
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull()
        ->and($context->multipleOf)->toBeNull();
})->with([
    'one digit includes 0' => [1, 0, 9],
    'three digits'         => [3, 100, 999],
    'eighteen digits'      => [18, 100000000000000000, 999999999999999999],
]);

it('writes only the bound an int holds exactly', function (int $digits, ?int $minimum) {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new Digits($digits), $context);

    expect($context->minimum)->toBe($minimum)
        ->and($context->maximum)->toBeNull()
        ->and($context->descriptions)->toBe(["Must have exactly <code>{$digits}</code> digits."]);
})->with([
    'nineteen digits' => [19, 1000000000000000000],
    'twenty digits'   => [20, null],
]);

it('documents a digit count on a number as a whole-number range', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Digits(3), $context);

    expect($context->minimum)->toBe(100)
        ->and($context->maximum)->toBe(999)
        ->and($context->multipleOf)->toBe(1);
});

it('keeps a multipleOf already set on a number', function () {
    $context = conditionContext();
    $context->type = 'number';
    $context->multipleOf = 5;

    $this->processor->process(new Digits(3), $context);

    expect($context->multipleOf)->toBe(5);
});

it('tightens bounds already on the context rather than overwriting them', function () {
    $integer = conditionContext();
    $integer->type = 'integer';
    $integer->minimum = 500;
    $integer->maximum = 2000;

    $this->processor->process(new Digits(3), $integer);

    $string = conditionContext();
    $string->type = 'string';
    $string->minLength = 1;
    $string->maxLength = 2;

    $this->processor->process(new Digits(3), $string);

    expect($integer->minimum)->toBe(500)
        ->and($integer->maximum)->toBe(999)
        ->and($string->minLength)->toBe(3)
        ->and($string->maxLength)->toBe(2)
        ->and($string->pattern)->toBe('^[0-9]{3}$');
});

it('replaces a digit pattern written by an earlier digit rule', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new DigitsBetweenProcessor())->process(new DigitsBetween(2, 8), $context);
    $this->processor->process(new Digits(3), $context);

    expect($context->pattern)->toBe('^[0-9]{3}$')
        ->and($context->minLength)->toBe(3)
        ->and($context->maxLength)->toBe(3);
});

it('keeps a pattern already set on a string', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->pattern = '^1[0-9]*$';

    $this->processor->process(new Digits(3), $context);

    expect($context->pattern)->toBe('^1[0-9]*$')
        ->and($context->minLength)->toBe(3);
});

it('writes no constraint on a type it cannot bound', function () {
    $context = conditionContext();
    $context->type = 'integer[]';

    $this->processor->process(new Digits(3), $context);

    expect($context->descriptions)->toBe(['Must have exactly <code>3</code> digits.'])
        ->and($context->minimum)->toBeNull()
        ->and($context->minItems)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
