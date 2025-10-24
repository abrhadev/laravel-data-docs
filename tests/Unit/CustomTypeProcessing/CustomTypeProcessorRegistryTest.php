<?php

use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessor;
use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessorRegistry;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

it('is a singleton', function () {
    $instance1 = CustomTypeProcessorRegistry::getInstance();
    $instance2 = CustomTypeProcessorRegistry::getInstance();

    expect($instance1)->toBe($instance2);
});

it('registers and retrieves processor', function () {
    $processor = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'string';
        }
    };

    $registry = CustomTypeProcessorRegistry::getInstance();
    $registry->register('TestClass', $processor);

    expect($registry->getProcessorFor('TestClass'))->toBe($processor);
});

it('returns null for unregistered processor', function () {
    $registry = CustomTypeProcessorRegistry::getInstance();

    expect($registry->getProcessorFor('NonExistentClass'))->toBeNull();
});

it('can register multiple processors', function () {
    $processor1 = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'string';
        }
    };

    $processor2 = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'number';
        }
    };

    $registry = CustomTypeProcessorRegistry::getInstance();
    $registry->register('Class1', $processor1);
    $registry->register('Class2', $processor2);

    expect($registry->getProcessorFor('Class1'))->toBe($processor1)
        ->and($registry->getProcessorFor('Class2'))->toBe($processor2);
});

it('overwrites processor when registering same class', function () {
    $processor1 = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'string';
        }
    };

    $processor2 = new class implements CustomTypeProcessor {
        public function process(string $className, ParameterContext $context): void
        {
            $context->type = 'number';
        }
    };

    $registry = CustomTypeProcessorRegistry::getInstance();
    $registry->register('TestClass', $processor1);
    $registry->register('TestClass', $processor2);

    expect($registry->getProcessorFor('TestClass'))->toBe($processor2);
});

it('throws exception when trying to unserialize', function () {
    $instance = CustomTypeProcessorRegistry::getInstance();
    $instance->__wakeup();
})->throws(Exception::class, 'Cannot unserialize singleton');
