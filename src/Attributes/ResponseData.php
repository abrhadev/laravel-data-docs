<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Specifies the response DTO class for an endpoint.
 *
 * When applied to a controller method, this attribute indicates which Laravel Data
 * class should be used as the response structure for API documentation generation.
 * The ResponseDataStrategy will read this attribute to automatically
 * generate response schema instead of using hardcoded classes.
 *
 * @example
 * ```php
 * #[ResponseData(UserResponse::class)]
 * public function show(User $user)
 * {
 *     return UserResponse::from($user);
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ResponseData implements DataDocsAttribute
{
    /**
     * @param  class-string  $dtoClass  The fully qualified class name of the response DTO
     */
    public function __construct(
        public readonly string $dtoClass
    ) {}
}
