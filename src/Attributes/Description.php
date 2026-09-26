<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Adds one or more custom description strings to a parameter or endpoint in API documentation.
 *
 * On Data properties, descriptions follow the generated type sentence and any #[In] / #[NotIn]
 * value sentences, and precede the other validation and requirement sentences, wherever the
 * attribute is declared.
 * On controller methods, they set the Scribe endpoint description and may be used alongside ResponseData.
 * The attribute is repeatable and accepts one or more strings.
 *
 * @example
 * ```php
 * class UserData extends Data
 * {
 *     #[Description('The unique identifier of the user')]
 *     public string $id;
 *
 *     #[Description('First sentence.', 'Second sentence.')]
 *     public string $name;
 *
 *     #[Description('Line A')]
 *     #[Description('Line B')]
 *     public string $notes;
 * }
 *
 * class UserController
 * {
 *     #[Description('Fetch a user by id.')]
 *     #[ResponseData(UserResponse::class)]
 *     public function show(int $id) {}
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Description implements DataDocsAttribute
{
    public readonly array $descriptions;

    public function __construct(string ...$descriptions)
    {
        $this->descriptions = $descriptions;
    }
}
