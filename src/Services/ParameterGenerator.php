<?php

namespace Abrha\LaravelDataDocs\Services;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipeline;
use Spatie\LaravelData\Support\DataConfig;

final class ParameterGenerator
{
    public function __construct(
        private readonly ParameterPipeline $pipeline,
        private readonly DataConfig $dataConfig,
    ) {}

    public function __invoke(string $className): array
    {
        if (!class_exists($className)) {
            return [];
        }

        return $this->extractParameters($className);
    }

    /**
     * @throws \ReflectionException
     */
    private function extractParameters(string $className, string $prefix = ''): array
    {
        $dataClass = $this->dataConfig->getDataClass($className);

        $parameters = [];

        foreach ($dataClass->properties as $property) {
            $propertyName = $property->name;
            $fullName = $prefix ? "$prefix.$propertyName" : $propertyName;

            $context = new ParameterContext(
                name: $fullName,
                property: $property,
            );

            $context = $this->pipeline->process($context);

            if ($context->isHidden) {
                continue;
            }

            $parameters[$fullName] = $context->toParameter();

            if ($context->hasNestedParameters || $context->hasArrayParameters) {
                $suffix = $context->hasArrayParameters ? '[]' : '';
                $nestedParameters = $this->extractParameters($context->dataClass, $fullName . $suffix);
                $parameters = array_merge($parameters, $nestedParameters);
            }
        }

        return $parameters;
    }
}
