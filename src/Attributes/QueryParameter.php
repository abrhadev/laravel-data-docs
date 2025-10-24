<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Marks a property as a query parameter.
 *
 * When applied to a property in a Laravel Data class, this attribute indicates
 * that the property should be treated as a URL query parameter rather than
 * a request body parameter (for non-GET requests).
 *
 * For GET requests, all parameters are treated as query parameters by default.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class QueryParameter implements DataDocsAttribute {}
