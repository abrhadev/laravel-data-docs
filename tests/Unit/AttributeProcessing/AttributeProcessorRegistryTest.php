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
