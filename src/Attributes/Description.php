<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Adds one or more custom description strings to a parameter in API documentation.
 *
 * Descriptions are appended to the generated type and validation sentences.
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
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Description implements DataDocsAttribute
{
    public readonly array $descriptions;

    public function __construct(string ...$descriptions)
    {
        $this->descriptions = $descriptions;
    }
}
