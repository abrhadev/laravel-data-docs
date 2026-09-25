<?php

namespace Abrha\LaravelDataDocs\Services;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipeline;
use Abrha\LaravelDataDocs\Pipeline\Stages\RequirementDescriptionStage;
use Abrha\LaravelDataDocs\ValueObjects\ConfirmationCompanion;
use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Spatie\LaravelData\Support\DataConfig;

/**
 * A property carrying #[Confirmed] may yield a second, synthesised parameter
 * for its confirmation field. Its required, nullable, location, type,
 * enumValues, example and openApiAttributes (minus default) are copied from the
 * source's finished parameter. This is the only requirement decision made
 * outside RequiredStage, and it infers nothing: the companion has no property
 * for the resolver to read, and confirmed runs only when the source does.
 */
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

        $declaredNames = array_map(fn($property) => $property->name, $dataClass->properties->all());

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

            $parameter = $context->toParameter();
            $parameters[$fullName] = $parameter;

            $companion = $context->confirmationCompanion;
            if ($companion !== null
                && !$context->hasNestedParameters
                && !$context->hasArrayParameters
                && !in_array($companion->name, $declaredNames, true)) {
                $companionName = $prefix ? "$prefix.{$companion->name}" : $companion->name;
                $parameters[$companionName] = $this->companionParameter($companionName, $parameter, $companion);
            }

            if ($context->hasNestedParameters || $context->hasArrayParameters) {
                $suffix = $context->hasArrayParameters ? '[]' : '';
                $nestedParameters = $this->extractParameters($context->dataClass, $fullName . $suffix);
                $parameters = array_merge($parameters, $nestedParameters);
            }
        }

        return $parameters;
    }

    private function companionParameter(string $name, Parameter $source, ConfirmationCompanion $companion): Parameter
    {
        $description = implode(' ', array_filter([
            $companion->matchSentence,
            $source->required ? '' : $companion->requiredWhenSentSentence,
            $source->nullable ? RequirementDescriptionStage::NULLABLE_SENTENCE : '',
        ]));

        return new Parameter(
            name: $name,
            type: $source->type,
            required: $source->required,
            nullable: $source->nullable,
            location: $source->location,
            description: $description,
            example: $source->example,
            enumValues: $source->enumValues,
            openApiAttributes: array_diff_key($source->openApiAttributes, ['default' => true]),
        );
    }
}
