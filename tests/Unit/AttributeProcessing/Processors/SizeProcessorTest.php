<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SizeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new SizeProcessor();
});

it('sets exact size for integer type', function () {
    $testData = new class (10) extends Data {
        public function __construct(
            #[Size(10)]
            public int $value,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'value');

    $context = new ParameterContext('value', $property);
    $context->type = 'integer';

    $attribute = new Size(10);
    $this->processor->process($attribute, $context);

    expect($context->minimum)->toBe(10)
        ->and($context->maximum)->toBe(10)
        ->and($context->descriptions)->toContain('Must be exactly <code>10</code>.');
});

it('sets exact size for string type', function () {
    $testData = new class ('123456') extends Data {
        public function __construct(
            #[Size(6)]
            public string $verificationCode,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'verificationCode');

    $context = new ParameterContext('verificationCode', $property);
    $context->type = 'string';

    $attribute = new Size(6);
    $this->processor->process($attribute, $context);

    expect($context->minLength)->toBe(6)
        ->and($context->maxLength)->toBe(6)
        ->and($context->descriptions)->toContain('Must have exactly <code>6</code> characters.');
});

it('sets exact size for array type', function () {
    $testData = new class ([1, 2, 3]) extends Data {
        public function __construct(
            #[Size(3)]
            public array $items,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'items');

    $context = new ParameterContext('items', $property);
    $context->type = 'integer[]';

    $attribute = new Size(3);
    $this->processor->process($attribute, $context);

    expect($context->minItems)->toBe(3)
        ->and($context->maxItems)->toBe(3)
        ->and($context->descriptions)->toContain('Must have exactly <code>3</code> items.');
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Size(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minLength)->toBeNull()
        ->and($context->maxLength)->toBeNull();
});

it('skips a non-finite bound it cannot document', function (float $bound) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Size($bound), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
})->with([INF, -INF, NAN]);

it('publishes an impossible size as a note, with no bound', function (string $type, string $note, float|int $size = 2.5) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Size($size), $context);

    expect($context->descriptions)->toBe([$note])
        ->and([$context->minLength, $context->maxLength, $context->minItems, $context->maxItems, $context->minimum, $context->maximum])->each->toBeNull();
})->with([
    'string'               => ['string', 'Note: must have exactly <code>2.5</code> characters, which no string can have, so any request that sends this field fails validation.'],
    'array'                => ['string[]', 'Note: must have exactly <code>2.5</code> items, which no array can have, so any request that sends this field fails validation.'],
    'integer'              => ['integer', 'Note: must be exactly <code>2.5</code>, which no integer can be, so any integer sent fails validation.'],
    'negative on a string' => ['string', 'Note: must have exactly <code>-1</code> characters, which no string can have, so any request that sends this field fails validation.', -1],
    'negative on an array' => ['string[]', 'Note: must have exactly <code>-1</code> items, which no array can have, so any request that sends this field fails validation.', -1],
]);

it('keeps a fractional size on a number field', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Size(2.5), $context);

    expect($context->minimum)->toBe(2.5)
        ->and($context->maximum)->toBe(2.5)
        ->and($context->descriptions)->toBe(['Must be exactly <code>2.5</code>.']);
});

it('treats an integral float size as an ordinary size', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Size(3.0), $context);

    expect($context->minLength)->toBe(3)
        ->and($context->maxLength)->toBe(3)
        ->and($context->descriptions)->toBe(['Must have exactly <code>3</code> characters.']);
});

it('uses the singular unit for an integral float size of one', function (string $type, string $sentence) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Size(1.0), $context);

    expect($context->descriptions)->toBe([$sentence]);
})->with([
    'string' => ['string', 'Must have exactly <code>1</code> character.'],
    'array'  => ['string[]', 'Must have exactly <code>1</code> item.'],
]);

it('publishes a numeric bound an int cannot hold exactly', function (float $bound) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Size($bound), $context);

    expect($context->descriptions)->toHaveCount(1)
        ->and($context->descriptions[0])->toContain("<code>{$bound}</code>")
        ->and($context->minimum)->toBe($bound)
        ->and($context->maximum)->toBe($bound);
})->with([0.5, 1e20]);
