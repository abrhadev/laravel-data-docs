<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Specifies a custom example value for API documentation.
 *
 * When applied to a property in a Laravel Data class, this attribute provides
 * a specific example value to be used in the generated API documentation.
 * This overrides the automatically generated examples.
 *
 * @example
 * ```php
 * class UserData extends Data
 * {
 *     #[Example('john.doe@example.com')]
 *     public string $email;
 *
 *     #[Example(42)]
 *     public int $age;
 *
 *     #[Example(['admin', 'editor'])]
 *     public array $roles;
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Example implements DataDocsAttribute
{
    public function __construct(public readonly mixed $value) {}
}
