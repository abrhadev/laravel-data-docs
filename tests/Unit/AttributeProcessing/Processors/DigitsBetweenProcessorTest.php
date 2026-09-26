<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsBetweenProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new DigitsBetweenProcessor();
});

it('documents a digit range on a string as a pattern and a length', function () {
    $testData = new class ('12345') extends Data {
        public function __construct(
            #[DigitsBetween(2, 8)]
            public string $accountNumber,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'accountNumber');

    $context = new ParameterContext('accountNumber', $property);
    $context->type = 'string';

    $attribute = new DigitsBetween(2, 8);
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('^[0-9]{2,8}$')
        ->and($context->minLength)->toBe(2)
        ->and($context->maxLength)->toBe(8)
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull()
        ->and($context->descriptions)->toContain('Must have between <code>2</code> and <code>8</code> digits.');
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new DigitsBetween(1, new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('skips zero minimum digits it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new DigitsBetween(0, 3), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('skips a zero maximum it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new DigitsBetween(3, 0), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('documents a digit range on an integer as the range of whole numbers', function (int $fewest, int $most, ?int $minimum, ?int $maximum) {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new DigitsBetween($fewest, $most), $context);

    expect($context->minimum)->toBe($minimum)
        ->and($context->maximum)->toBe($maximum)
        ->and($context->pattern)->toBeNull();
})->with([
    'from one digit includes 0' => [1, 3, 0, 999],
    'two to eight'              => [2, 8, 10, 99999999],
    'upper beyond an int'       => [3, 19, 100, null],
    'both beyond an int'        => [20, 25, null, null],
]);

it('documents a digit range on a number as a whole-number range', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new DigitsBetween(2, 4), $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(9999)
        ->and($context->multipleOf)->toBe(1);
});

it('tightens bounds already on the context rather than overwriting them', function () {
    $context = conditionContext();
    $context->type = 'integer';
    $context->minimum = 5;
    $context->maximum = 500;

    $this->processor->process(new DigitsBetween(2, 4), $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(500);
});

it('publishes a reversed digit range as a note and no constraint', function (string $type) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new DigitsBetween(5, 2), $context);

    expect($context->descriptions)->toBe(['Note: must have between <code>5</code> and <code>2</code> digits, which no value can have, so any request that sends this field fails validation.'])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull()
        ->and($context->minLength)->toBeNull()
        ->and($context->maxLength)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->multipleOf)->toBeNull();
})->with(['string', 'integer', 'number']);

it('narrows the digit pattern to a length already on the context', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->minLength = 5;

    $this->processor->process(new DigitsBetween(2, 8), $context);

    expect($context->pattern)->toBe('^[0-9]{5,8}$')
        ->and($context->minLength)->toBe(5)
        ->and($context->maxLength)->toBe(8);
});
