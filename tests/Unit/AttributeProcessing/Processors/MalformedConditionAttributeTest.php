<?php

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\AcceptedIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ConfirmedProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DifferentProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithoutProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\InArrayProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitsProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithAllProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutAllProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SameProcessor;

/*
 * A documentation build must not fail over one attribute. These stand-ins
 * report parameters() the way a malformed or future upstream attribute could,
 * and each processor must quietly document nothing.
 */

dataset('condition processors', fn() => [
    'AcceptedIf'         => [new AcceptedIfProcessor()],
    'DeclinedIf'         => [new DeclinedIfProcessor()],
    'Different'          => [new DifferentProcessor()],
    'ExcludeIf'          => [new ExcludeIfProcessor()],
    'ExcludeUnless'      => [new ExcludeUnlessProcessor()],
    'ExcludeWith'        => [new ExcludeWithProcessor()],
    'ExcludeWithout'     => [new ExcludeWithoutProcessor()],
    'InArray'            => [new InArrayProcessor()],
    'ProhibitedIf'       => [new ProhibitedIfProcessor()],
    'ProhibitedUnless'   => [new ProhibitedUnlessProcessor()],
    'Prohibits'          => [new ProhibitsProcessor()],
    'RequiredIf'         => [new RequiredIfProcessor()],
    'RequiredUnless'     => [new RequiredUnlessProcessor()],
    'RequiredWith'       => [new RequiredWithProcessor()],
    'RequiredWithAll'    => [new RequiredWithAllProcessor()],
    'RequiredWithout'    => [new RequiredWithoutProcessor()],
    'RequiredWithoutAll' => [new RequiredWithoutAllProcessor()],
    'Same'               => [new SameProcessor()],
]);

it('documents nothing when parameters() throws', function (AttributeProcessor $processor) {
    $context = conditionContext();

    $processor->process(new MalformedConditionAttributeTestThrowing(), $context);

    expect($context->descriptions)->toBe([]);
})->with('condition processors');

it('documents nothing when parameters() reports no values', function (AttributeProcessor $processor) {
    $context = conditionContext();

    $processor->process(new MalformedConditionAttributeTestReporting([]), $context);

    expect($context->descriptions)->toBe([]);
})->with('condition processors');

it('documents nothing when the referenced field name is empty', function (AttributeProcessor $processor) {
    $context = conditionContext();

    $processor->process(new MalformedConditionAttributeTestReporting(['']), $context);

    expect($context->descriptions)->toBe([]);
})->with([
    'Different' => [new DifferentProcessor()],
    'InArray'   => [new InArrayProcessor()],
    'Same'      => [new SameProcessor()],
]);

it('documents no companion when Confirmed parameters() throws', function () {
    $context = conditionContext();

    (new ConfirmedProcessor())->process(new MalformedConditionAttributeTestThrowing(), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->confirmationCompanion)->toBeNull();
});

class MalformedConditionAttributeTestThrowing
{
    public function parameters(): array
    {
        throw new Error('Typed property must not be accessed before initialization');
    }
}

class MalformedConditionAttributeTestReporting
{
    public function __construct(private readonly array $parameters) {}

    public function parameters(): array
    {
        return $this->parameters;
    }
}
