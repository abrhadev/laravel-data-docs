<?php

namespace Abrha\LaravelDataDocs\CustomTypeProcessing;

final class CustomTypeProcessorRegistry
{
    private static ?self $instance = null;

    private array $processors = [];

    private function __construct()
    {
        $this->registerDefaults();
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(string $className, CustomTypeProcessor $processor): void
    {
        $this->processors[$className] = $processor;
    }

    public function getProcessorFor(string $className): ?CustomTypeProcessor
    {
        return $this->processors[$className] ?? null;
    }

    private function registerDefaults(): void {}
}
