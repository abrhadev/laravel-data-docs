<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedIfProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

it('states nothing for a prohibition a later rule of its class replaces', function () {
    $property = app(DataConfig::class)
        ->getDataClass(ProhibitionExclusionEdgeCasesTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'replaced');
    $context = new ParameterContext($property->name, $property);

    (new ProhibitedIfProcessor())->process($property->attributes->first(ProhibitedIf::class), $context);

    expect($context->descriptions)->toBe([]);
});

class ProhibitionExclusionEdgeCasesTestData extends Data
{
    public function __construct(
        #[ProhibitedIf('a', 'x'), Rule('prohibited_if:a,y')]
        public ?string $replaced,
        public string $a = '',
    ) {}
}
