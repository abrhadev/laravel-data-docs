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

it('throws exception when trying to unserialize', function () {
    $instance = AttributeProcessorRegistry::getInstance();
    $instance->__wakeup();
})->throws(Exception::class, 'Cannot unserialize singleton');
