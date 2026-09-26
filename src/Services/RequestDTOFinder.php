<?php

namespace Abrha\LaravelDataDocs\Services;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use Spatie\LaravelData\Contracts\ValidateableData;

final class RequestDTOFinder
{
    private static ?self $instance = null;

    private function __construct() {}

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

    public function __invoke(ReflectionFunctionAbstract $method): ?ReflectionClass
    {
        foreach ($method->getParameters() as $parameter) {
            $parameterType = $parameter->getType();

            if (!$parameterType instanceof ReflectionNamedType) {
                continue;
            }

            $parameterClassName = $parameterType->getName();

            if (!class_exists($parameterClassName)) {
                continue;
            }

            $parameterClass = new ReflectionClass($parameterClassName);

            // Laravel Data validates and injects any of these from the request
            // (Data and Dto), not a Resource, which is output only.
            if ($parameterClass->implementsInterface(ValidateableData::class)) {
                return $parameterClass;
            }
        }

        return null;
    }
}
