<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MaxProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

it('rounds a fractional length bound down', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new MaxProcessor())->process(new Max(2.5), $context);

    expect($context->maxLength)->toBe(2);
});

it('writes no negative length bound', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new MaxProcessor())->process(new Max(-1.5), $context);

    expect($context->maxLength)->toBeNull();
});

beforeEach(function () {
    $this->processor = new MaxProcessor();
});

it('sets maximum for integer type', function () {
    $testData = new class (50) extends Data {
        public function __construct(
            #[Max(100)]
            public int $score,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'score');

    $context = new ParameterContext('score', $property);
    $context->type = 'integer';

    $attribute = new Max(100);
    $this->processor->process($attribute, $context);

    expect($context->maximum)->toBe(100)
        ->and($context->maxLength)->toBeNull()
        ->and($context->maxItems)->toBeNull()
        ->and($context->descriptions)->toContain('Must be maximum <code>100</code>.');
});

it('sets maxLength for string type', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Max(50)]
            public string $title,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'title');

    $context = new ParameterContext('title', $property);
    $context->type = 'string';

    $attribute = new Max(50);
    $this->processor->process($attribute, $context);

    expect($context->maxLength)->toBe(50)
        ->and($context->maximum)->toBeNull()
        ->and($context->maxItems)->toBeNull()
        ->and($context->descriptions)->toContain('Must have maximum <code>50</code> characters.');
});

it('sets maxItems for array type', function () {
    $testData = new class ([]) extends Data {
        public function __construct(
            #[Max(10)]
            public array $items,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'items');

    $context = new ParameterContext('items', $property);
    $context->type = 'string[]';

    $attribute = new Max(10);
    $this->processor->process($attribute, $context);

    expect($context->maxItems)->toBe(10)
        ->and($context->maximum)->toBeNull()
        ->and($context->maxLength)->toBeNull()
        ->and($context->descriptions)->toContain('Must have maximum <code>10</code> items.');
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Max(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->maximum)->toBeNull()
        ->and($context->maxLength)->toBeNull()
        ->and($context->maxItems)->toBeNull();
});

it('skips a non-finite bound it cannot document', function (float $bound) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Max($bound), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBeNull();
})->with([INF, -INF, NAN]);

it('publishes a numeric bound an int cannot hold exactly', function (float $bound) {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Max($bound), $context);

    expect($context->descriptions)->toHaveCount(1)
        ->and($context->descriptions[0])->toContain("<code>{$bound}</code>")
        ->and($context->minimum)->toBeNull()
        ->and($context->maximum)->toBe($bound);
})->with([0.5, 1e20]);

it('writes a float bound an int holds exactly as an integer constraint', function () {
    $context = conditionContext();
    $context->type = 'number';

    $this->processor->process(new Max(10.0), $context);

    expect($context->maximum)->toBe(10);
});
