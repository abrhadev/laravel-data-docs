<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeUnlessProcessor;
use Spatie\LaravelData\Attributes\Validation\ExcludeUnless;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new ExcludeUnlessProcessor();
});

it('states the condition for each kind of literal value', function (mixed $value, string $rendered) {
    $context = conditionContext();

    $this->processor->process(new ExcludeUnless('pricing_mode', $value), $context);

    expect($context->descriptions)->toBe([
        "Not validated, and removed from the validated input unless <b><i>pricing_mode</i></b> is {$rendered}.",
    ]);
})->with([
    'string'      => ['manual', '<code>manual</code>'],
    'int'         => [3, '<code>3</code>'],
    'float'       => [1.5, '<code>1.5</code>'],
    'true'        => [true, '<code>true</code>'],
    'false'       => [false, '<code>false</code>'],
    'backed enum' => [ConditionAccountType::Charity, '<code>Charity</code> (charity)'],
]);

it('degrades to a value-free sentence for an external reference', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeUnless('pricing_mode', new RouteParameterReference('mode')), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input depending on the value of <b><i>pricing_mode</i></b>.',
    ]);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeUnless('pricing_mode', 'manual'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
