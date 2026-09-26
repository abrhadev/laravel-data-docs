<?php

use Abrha\LaravelDataDocs\Attributes\Description;
use Abrha\LaravelDataDocs\Attributes\Example;
use Abrha\LaravelDataDocs\Attributes\Hidden;
use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\NotIn;
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

it('places the description after the In and NotIn value sentences, wherever they are declared', function () {
    $dataProperty = app(DataConfig::class)
        ->getDataClass(DescriptionOrderTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'status');

    $context = PipelineFactory::createDefault()->process(new ParameterContext($dataProperty->name, $dataProperty));

    expect($context->description)->toStartWith(
        'Must be a string. Must be one of: <code>a</code>, <code>b</code>. Custom text. Must have maximum <code>5</code> characters.'
    );
});

it('places the description after a lone NotIn sentence declared after it', function () {
    $dataProperty = app(DataConfig::class)
        ->getDataClass(DescriptionOrderTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'tag');

    $context = PipelineFactory::createDefault()->process(new ParameterContext($dataProperty->name, $dataProperty));

    expect($context->description)->toBe(
        'Must be a string. Must not be one of: <code>x</code>. Custom. Must have maximum <code>3</code> characters. '
        . 'A null value is accepted.'
    );
});

it('treats consumer subclasses of Example, QueryParameter and Hidden as their parents', function () {
    $parameters = (new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class)))(DescriptionOrderTestSubclassData::class);

    expect($parameters)->toHaveKeys(['code', 'page'])
        ->not->toHaveKey('secret')
        ->and($parameters['code']->example)->toBe('ABC-123')
        ->and($parameters['page']->location)->toBe(ParameterLocation::QUERY)
        ->and($parameters['code']->location)->toBe(ParameterLocation::BODY);
});

#[Attribute(Attribute::TARGET_PROPERTY)]
class DescriptionOrderTestExample extends Example {}

#[Attribute(Attribute::TARGET_PROPERTY)]
class DescriptionOrderTestQueryParameter extends QueryParameter {}

#[Attribute(Attribute::TARGET_PROPERTY)]
class DescriptionOrderTestHidden extends Hidden {}

class DescriptionOrderTestSubclassData extends Data
{
    public function __construct(
        #[DescriptionOrderTestExample('ABC-123')]
        public string $code,
        #[DescriptionOrderTestQueryParameter]
        public ?int $page,
        #[DescriptionOrderTestHidden]
        public ?string $secret,
    ) {}
}

class DescriptionOrderTestData extends Data
{
    public function __construct(
        #[Max(5), Present, Description('Custom text.')]
        public ?string $code,
        #[In(['a', 'b', 'c']), Description('Custom text.'), Max(5), NotIn(['c'])]
        public string $status,
        #[Description('Custom.'), NotIn(['x']), Max(3)]
        public ?string $tag,
    ) {}
}
