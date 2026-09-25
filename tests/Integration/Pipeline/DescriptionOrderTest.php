<?php

use Abrha\LaravelDataDocs\Attributes\Description;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

it('places custom description text after the type sentence and before validation and requirement sentences', function () {
    // Pins the order the #[Description] docblock states, through the assembled
    // default pipeline where stage order is observable.
    $dataProperty = app(DataConfig::class)
        ->getDataClass(DescriptionOrderTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'code');

    $context = PipelineFactory::createDefault()->process(new ParameterContext($dataProperty->name, $dataProperty));

    expect($context->description)->toBe(
        'Must be a string. Custom text. Must have maximum <code>5</code> characters. '
        . 'Must be included in the request, but may be empty. A null value is accepted.'
    );
});

class DescriptionOrderTestData extends Data
{
    public function __construct(
        #[Max(5), Present, Description('Custom text.')]
        public ?string $code,
    ) {}
}
