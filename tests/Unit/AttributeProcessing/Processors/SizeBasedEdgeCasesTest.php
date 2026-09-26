<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\BetweenProcessor;
use Spatie\LaravelData\Attributes\Validation\Between;

it('states a reversed range as declared on a field whose type it cannot judge', function () {
    $context = conditionContext();
    $context->type = 'boolean';

    (new BetweenProcessor())->process(new Between(5, 2), $context);

    expect($context->descriptions)->toBe(['Must have between <code>5</code> and <code>2</code> characters.']);
});
