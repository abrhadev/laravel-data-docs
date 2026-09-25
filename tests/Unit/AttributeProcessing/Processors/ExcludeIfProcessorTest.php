<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeIfProcessor;
use Spatie\LaravelData\Attributes\Validation\ExcludeIf;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new ExcludeIfProcessor();
});

it('states the condition for each kind of literal value', function (mixed $value, string $rendered) {
    $context = conditionContext();

    $this->processor->process(new ExcludeIf('order_type', $value), $context);

    expect($context->descriptions)->toBe([
        "Not validated, and removed from the validated input when <b><i>order_type</i></b> is {$rendered}.",
    ]);
})->with([
    'string'      => ['internal', '<code>internal</code>'],
    'int'         => [3, '<code>3</code>'],
    'float'       => [1.5, '<code>1.5</code>'],
    'true'        => [true, '<code>true</code>'],
    'false'       => [false, '<code>false</code>'],
    'backed enum' => [ConditionAccountType::Business, '<code>Business</code> (business)'],
]);

it('degrades to a value-free sentence for an external reference', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeIf('order_type', new RouteParameterReference('kind')), $context);

    expect($context->descriptions)->toBe([
        'Not validated, and removed from the validated input depending on the value of <b><i>order_type</i></b>.',
    ]);
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new ExcludeIf('order_type', 'internal'), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull();
});
