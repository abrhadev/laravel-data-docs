<?php

use Abrha\LaravelDataDocs\Attributes\Hidden;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\HiddenStage;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->dataConfig = app(DataConfig::class);
    $this->stage = new HiddenStage();
});

it('marks context as hidden when Hidden attribute is present', function () {
    $dataClass = $this->dataConfig->getDataClass(HiddenTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'secret');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->isHidden)->toBeTrue();
});

it('does not mark context as hidden when Hidden attribute is absent', function () {
    $dataClass = $this->dataConfig->getDataClass(HiddenTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'visible');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->isHidden)->toBeFalse();
});

it('preserves existing context properties', function () {
    $dataClass = $this->dataConfig->getDataClass(HiddenTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'visible');

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->required = true;

    $result = $this->stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->required)->toBeTrue();
});

class HiddenTestData extends Data
{
    public function __construct(
        public string $visible,
        #[Hidden]
        public string $secret,
    ) {}
}
