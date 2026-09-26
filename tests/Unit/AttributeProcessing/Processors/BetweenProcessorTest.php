<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\BetweenProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new BetweenProcessor();
});

it('sets minimum and maximum for integer type', function () {
    $testData = new class (50) extends Data {
        public function __construct(
            #[Between(10, 100)]
            public int $score,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'score');

    $context = new ParameterContext('score', $property);
    $context->type = 'integer';

    $attribute = new Between(10, 100);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(100)
        ->and($context->descriptions)->toContain('Must be between <code>10</code> and <code>100</code>.');
});

it('sets minLength and maxLength for string type', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Between(5, 20)]
            public string $description,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'description');

    $context = new ParameterContext('description', $property);
    $context->type = 'string';

    $attribute = new Between(5, 20);
    $this->processor->process($attribute, $context);

    expect($context->minLength)->toBe(5)
        ->and($context->maxLength)->toBe(20)
        ->and($context->descriptions)->toContain('Must have between <code>5</code> and <code>20</code> characters.');
});

it('publishes no negative length or item-count bound', function (string $type, string $lower, string $upper) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Between(-2, 3), $context);

    expect($context->{$lower})->toBeNull()
        ->and($context->{$upper})->toBe(3);
})->with([
    'string' => ['string', 'minLength', 'maxLength'],
    'array'  => ['string[]', 'minItems', 'maxItems'],
]);

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'integer';

    $this->processor->process(new Between(1, new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('skips a non-finite bound it cannot document', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Between(1, INF), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
});

it('publishes a range with no whole number as a note, with no bound', function (string $type, float|int $low, float|int $high, string $note) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Between($low, $high), $context);

    expect($context->descriptions)->toBe([$note])
        ->and([$context->minLength, $context->maxLength, $context->minItems, $context->maxItems, $context->minimum, $context->maximum])->each->toBeNull();
})->with([
    'string'                         => ['string', 2.2, 2.8, 'Note: must have between <code>2.2</code> and <code>2.8</code> characters, which no string can have, so any request that sends this field fails validation.'],
    'array'                          => ['string[]', 2.2, 2.8, 'Note: must have between <code>2.2</code> and <code>2.8</code> items, which no array can have, so any request that sends this field fails validation.'],
    'integer'                        => ['integer', 2.2, 2.8, 'Note: must be between <code>2.2</code> and <code>2.8</code>, which no integer can be, so any integer sent fails validation.'],
    'reversed'                       => ['string', 5, 2, 'Note: must have between <code>5</code> and <code>2</code> characters, which no string can have, so any request that sends this field fails validation.'],
    'reversed on a number'           => ['number', 5, 2, 'Note: must be between <code>5</code> and <code>2</code>, which no number can be, so any request that sends this field fails validation.'],
    'reversed fractions on a number' => ['number', 2.8, 2.2, 'Note: must be between <code>2.8</code> and <code>2.2</code>, which no number can be, so any request that sends this field fails validation.'],
    'negative on a string'           => ['string', -5, -2, 'Note: must have between <code>-5</code> and <code>-2</code> characters, which no string can have, so any request that sends this field fails validation.'],
    'negative on an array'           => ['string[]', -5, -2, 'Note: must have between <code>-5</code> and <code>-2</code> items, which no array can have, so any request that sends this field fails validation.'],
]);

it('keeps a range that holds a whole number, and a non-reversed range on a number field', function (string $type, string $field, float|int $bound, float|int $low = 2.5, float|int $high = 3.5) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Between($low, $high), $context);

    expect($context->$field)->toBe($bound)
        ->and($context->descriptions[0])->not->toStartWith('Note:');
})->with([
    'integer holding 3'       => ['integer', 'minimum', 2.5],
    'number'                  => ['number', 'maximum', 3.5],
    'number, one-value range' => ['number', 'minimum', 2.5, 2.5, 2.5],
    'string holding 3'        => ['string', 'minLength', 3],
]);

it('writes fractional length bounds as the whole numbers they mean', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Between(1.5, 3.5), $context);

    expect($context->minLength)->toBe(2)
        ->and($context->maxLength)->toBe(3);
});

it('writes an integral bound as an int and a fractional one as a float', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Between(1, 2.5), $context);

    expect($context->descriptions)->toBe(['Must be between <code>1</code> and <code>2.5</code>.'])
        ->and($context->minimum)->toBe(1)
        ->and($context->maximum)->toBe(2.5);
});

it('uses the singular unit for an integral float range of one', function () {
    $testData = new class ('a') extends Data {
        public function __construct(
            public string $code,
        ) {}
    };

    $property = app(DataConfig::class)->getDataClass($testData::class)->properties->first();
    $context = new ParameterContext('code', $property);
    $context->type = 'string';

    $this->processor->process(new Between(1.0, 1.0), $context);

    expect($context->descriptions)->toBe(['Must have between <code>1</code> and <code>1</code> character.']);
});
