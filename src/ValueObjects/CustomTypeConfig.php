<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

use InvalidArgumentException;

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
        foreach (['type', 'descriptions'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new InvalidArgumentException("Custom type config is missing the required key [{$key}].");
            }
        }

        return new self(
            type: $config['type'],
            descriptions: $config['descriptions'],
            pattern: $config['pattern'] ?? null,
            format: $config['format'] ?? null,
            minimum: self::bound($config, 'minimum'),
            maximum: self::bound($config, 'maximum'),
            exclusiveMinimum: self::bound($config, 'exclusiveMinimum'),
            exclusiveMaximum: self::bound($config, 'exclusiveMaximum'),
            minLength: self::bound($config, 'minLength', nonNegative: true),
            maxLength: self::bound($config, 'maxLength', nonNegative: true),
            minItems: self::bound($config, 'minItems', nonNegative: true),
            maxItems: self::bound($config, 'maxItems', nonNegative: true),
            multipleOf: self::bound($config, 'multipleOf', positive: true),
        );
    }

    /**
     * Bounds are published as ints. A value an int cannot hold exactly is a
     * configuration error: truncating it would publish a wrong bound, and
     * dropping it would publish none without telling the developer. OpenAPI also
     * requires lengths and item counts to be non-negative and multipleOf positive.
     */
    private static function bound(array $config, string $key, bool $positive = false, bool $nonNegative = false): ?int
    {
        $value = $config[$key] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_string($value) && is_numeric($value)) {
            $value = $value + 0;
        }

        $bound = match (true) {
            is_int($value)                                                                                => $value,
            is_float($value) && floor($value) === $value && $value >= PHP_INT_MIN && $value < PHP_INT_MAX => (int) $value,
            default                                                                                       => null,
        };

        if ($bound === null) {
            throw new InvalidArgumentException(sprintf(
                'Custom type config [%s] must be an integer, got %s.',
                $key,
                var_export($value, true),
            ));
        }

        if ($nonNegative && $bound < 0) {
            throw new InvalidArgumentException(sprintf(
                'Custom type config [%s] must not be negative, got %d.',
                $key,
                $bound,
            ));
        }

        if ($positive && $bound <= 0) {
            throw new InvalidArgumentException(sprintf(
                'Custom type config [%s] must be greater than zero, got %d.',
                $key,
                $bound,
            ));
        }

        return $bound;
    }
}
