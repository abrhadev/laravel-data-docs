<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\InProcessor;
use Spatie\LaravelData\Attributes\Validation\In;

beforeEach(function () {
    $this->processor = new InProcessor();
});

it('reads the values of a collection passed to In', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new In(collect(['draft', 'published'])), $context);

    expect($context->allowedValues)->toBe(['draft', 'published']);
});

it('documents nothing for an In subclass that never sets its values', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new ValueListEdgeCasesUninitialisedIn(), $context);

    expect($context->allowedValues)->toBeNull()
        ->and($context->descriptions)->toBe([]);
});

it('keeps only the values every In on the field accepts', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new In(['a', 'b', 'c']), $context);
    $this->processor->process(new In(['b', 'c', 'd']), $context);

    expect($context->allowedValues)->toBe(['b', 'c']);
});

class ValueListEdgeCasesUninitialisedIn extends In
{
    public function __construct() {}
}
