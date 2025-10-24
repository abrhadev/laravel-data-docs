<?php

namespace Abrha\LaravelDataDocs\Services;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use Spatie\LaravelData\Data;

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

            if ($parameterClass->isSubclassOf(Data::class)) {
                return $parameterClass;
            }
        }

        return null;
    }
}
