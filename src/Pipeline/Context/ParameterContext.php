<?php

namespace Abrha\LaravelDataDocs\Pipeline\Context;

use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Spatie\LaravelData\Support\DataProperty;

final class ParameterContext
{
    public bool $isHidden = false;

    public bool $hasNestedParameters = false;

    public bool $hasArrayParameters = false;

    public ?string $type = null;

    public ?bool $required = null;

    public ?bool $nullable = null;

    public ?ParameterLocation $location = null;

    public string $description = '';

    public mixed $example = null;

    public ?EnumInfo $enumInfo = null;

    public ?string $dataClass = null;

    public mixed $default = null;

    public array $descriptions = [];

    public ?string $format = null;

    public ?int $minimum = null;

    public ?int $maximum = null;

    public ?int $exclusiveMinimum = null;

    public ?int $exclusiveMaximum = null;

    public ?string $pattern = null;

    public ?int $minLength = null;

    public ?int $maxLength = null;

    public ?int $minItems = null;

    public ?int $maxItems = null;

    public ?int $multipleOf = null;

    public function __construct(
        public readonly string $name,
        public readonly DataProperty $property,
    ) {}

    public function toParameter(): Parameter
    {
        return new Parameter(
            name: $this->name,
            type: $this->type ?? 'string',
            required: $this->required ?? false,
            nullable: $this->nullable ?? true,
            location: $this->location ?? ParameterLocation::BODY,
            description: $this->description,
            example: $this->example,
            enumValues: $this->enumInfo?->toArray(),
            openApiAttributes: array_filter([
                'default'          => $this->default,
                'format'           => $this->format,
                'minimum'          => $this->minimum,
                'maximum'          => $this->maximum,
                'exclusiveMinimum' => $this->exclusiveMinimum,
                'exclusiveMaximum' => $this->exclusiveMaximum,
                'pattern'          => $this->pattern,
                'minLength'        => $this->minLength,
                'maxLength'        => $this->maxLength,
                'minItems'         => $this->minItems,
                'maxItems'         => $this->maxItems,
                'multipleOf'       => $this->multipleOf,
            ], fn($value) => $value !== null)
        );
    }
}
