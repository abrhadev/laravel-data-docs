<?php

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessorRegistry;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Url;

it('implements singleton pattern', function () {
    $instance1 = AttributeProcessorRegistry::getInstance();
    $instance2 = AttributeProcessorRegistry::getInstance();

    expect($instance1)->toBe($instance2);
});

it('refuses to be unserialized', function () {
    AttributeProcessorRegistry::getInstance()->__wakeup();
})->throws(Exception::class, 'Cannot unserialize singleton');

it('registers and retrieves processors', function () {
    $registry = AttributeProcessorRegistry::getInstance();

    $customProcessor = new class implements AttributeProcessor {
        public function process(object $attribute, ParameterContext $context): void
        {
            $context->descriptions[] = 'Custom processor';
        }
    };

    $registry->register('CustomAttribute', $customProcessor);

    $retrieved = $registry->getProcessorFor('CustomAttribute');

    expect($retrieved)->toBe($customProcessor);
});

it('returns null for unregistered attribute', function () {
    $registry = AttributeProcessorRegistry::getInstance();

    $processor = $registry->getProcessorFor('NonExistentAttribute');

    expect($processor)->toBeNull();
});

it('registers all default processors', function () {
    $registry = AttributeProcessorRegistry::getInstance();

    $emailProcessor = $registry->getProcessorFor(Email::class);
    $minProcessor = $registry->getProcessorFor(Min::class);
    $urlProcessor = $registry->getProcessorFor(Url::class);

    expect($emailProcessor)->not->toBeNull()
        ->and($minProcessor)->not->toBeNull()
        ->and($urlProcessor)->not->toBeNull();
});

it('registers a processor for every conditional requirement attribute', function (string $attribute, string $processor) {
    expect(AttributeProcessorRegistry::getInstance()->getProcessorFor($attribute))
        ->toBeInstanceOf($processor);
})->with([
    'RequiredIf'         => [Spatie\LaravelData\Attributes\Validation\RequiredIf::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredIfProcessor::class],
    'RequiredUnless'     => [Spatie\LaravelData\Attributes\Validation\RequiredUnless::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredUnlessProcessor::class],
    'RequiredWith'       => [Spatie\LaravelData\Attributes\Validation\RequiredWith::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithProcessor::class],
    'RequiredWithAll'    => [Spatie\LaravelData\Attributes\Validation\RequiredWithAll::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithAllProcessor::class],
    'RequiredWithout'    => [Spatie\LaravelData\Attributes\Validation\RequiredWithout::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutProcessor::class],
    'RequiredWithoutAll' => [Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutAllProcessor::class],
]);

it('registers a processor for every prohibition and exclusion attribute', function (string $attribute, string $processor) {
    expect(AttributeProcessorRegistry::getInstance()->getProcessorFor($attribute))
        ->toBeInstanceOf($processor);
})->with([
    'Prohibited'       => [Spatie\LaravelData\Attributes\Validation\Prohibited::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedProcessor::class],
    'ProhibitedIf'     => [Spatie\LaravelData\Attributes\Validation\ProhibitedIf::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedIfProcessor::class],
    'ProhibitedUnless' => [Spatie\LaravelData\Attributes\Validation\ProhibitedUnless::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedUnlessProcessor::class],
    'Prohibits'        => [Spatie\LaravelData\Attributes\Validation\Prohibits::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitsProcessor::class],
    'Exclude'          => [Spatie\LaravelData\Attributes\Validation\Exclude::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeProcessor::class],
    'ExcludeIf'        => [Spatie\LaravelData\Attributes\Validation\ExcludeIf::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeIfProcessor::class],
    'ExcludeUnless'    => [Spatie\LaravelData\Attributes\Validation\ExcludeUnless::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeUnlessProcessor::class],
    'ExcludeWith'      => [Spatie\LaravelData\Attributes\Validation\ExcludeWith::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithProcessor::class],
    'ExcludeWithout'   => [Spatie\LaravelData\Attributes\Validation\ExcludeWithout::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithoutProcessor::class],
]);

it('registers a processor for every cross-field comparison and acceptance attribute', function (string $attribute, string $processor) {
    expect(AttributeProcessorRegistry::getInstance()->getProcessorFor($attribute))
        ->toBeInstanceOf($processor);
})->with([
    'Same'       => [Spatie\LaravelData\Attributes\Validation\Same::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\SameProcessor::class],
    'Different'  => [Spatie\LaravelData\Attributes\Validation\Different::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\DifferentProcessor::class],
    'InArray'    => [Spatie\LaravelData\Attributes\Validation\InArray::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\InArrayProcessor::class],
    'Confirmed'  => [Spatie\LaravelData\Attributes\Validation\Confirmed::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\ConfirmedProcessor::class],
    'Accepted'   => [Spatie\LaravelData\Attributes\Validation\Accepted::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\AcceptedProcessor::class],
    'AcceptedIf' => [Spatie\LaravelData\Attributes\Validation\AcceptedIf::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\AcceptedIfProcessor::class],
    'Declined'   => [Spatie\LaravelData\Attributes\Validation\Declined::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedProcessor::class],
    'DeclinedIf' => [Spatie\LaravelData\Attributes\Validation\DeclinedIf::class, Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedIfProcessor::class],
]);

it('registers a description-only processor for Filled', function () {
    expect(AttributeProcessorRegistry::getInstance()->getProcessorFor(Spatie\LaravelData\Attributes\Validation\Filled::class))
        ->toBeInstanceOf(Abrha\LaravelDataDocs\AttributeProcessing\Processors\StaticAttributeProcessor::class);
});

it('registers no processor for the attributes RequiredStage owns', function (string $attribute) {
    // RequiredStage assigns required/nullable/onlyValidatedWhenPresent
    // unconditionally and runs after AttributeProcessingStage, so a processor
    // for any of these would be silently discarded.
    expect(AttributeProcessorRegistry::getInstance()->getProcessorFor($attribute))->toBeNull();
})->with([
    'Required'  => [Spatie\LaravelData\Attributes\Validation\Required::class],
    'Nullable'  => [Spatie\LaravelData\Attributes\Validation\Nullable::class],
    'Sometimes' => [Spatie\LaravelData\Attributes\Validation\Sometimes::class],
    // Its sentence depends on the published requirement status, so
    // RequirementDescriptionStage writes it after RequiredStage.
    'Present' => [Spatie\LaravelData\Attributes\Validation\Present::class],
]);

it('throws exception when trying to unserialize', function () {
    $instance = AttributeProcessorRegistry::getInstance();
    $instance->__wakeup();
})->throws(Exception::class, 'Cannot unserialize singleton');
