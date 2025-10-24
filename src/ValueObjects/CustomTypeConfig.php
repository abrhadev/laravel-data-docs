<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

final class CustomTypeConfig
{
    public function __construct(
        public readonly string $type,
        public readonly array $descriptions,
        public readonly ?string $pattern = null,
        public readonly ?string $format = null,
        public readonly ?int $minimum = null,
        public readonly ?int $maximum = null,
        public readonly ?int $exclusiveMinimum = null,
        public readonly ?int $exclusiveMaximum = null,
        public readonly ?int $minLength = null,
        public readonly ?int $maxLength = null,
        public readonly ?int $minItems = null,
        public readonly ?int $maxItems = null,
        public readonly ?int $multipleOf = null,
    ) {}

    public static function fromArray(array $config): self
    {
        return new self(
            type: $config['type'],
            descriptions: $config['descriptions'],
            pattern: $config['pattern'] ?? null,
            format: $config['format'] ?? null,
            minimum: $config['minimum'] ?? null,
            maximum: $config['maximum'] ?? null,
            exclusiveMinimum: $config['exclusiveMinimum'] ?? null,
            exclusiveMaximum: $config['exclusiveMaximum'] ?? null,
            minLength: $config['minLength'] ?? null,
            maxLength: $config['maxLength'] ?? null,
            minItems: $config['minItems'] ?? null,
            maxItems: $config['maxItems'] ?? null,
            multipleOf: $config['multipleOf'] ?? null,
        );
    }
}
