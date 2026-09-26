<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Marks a property as a query parameter.
 *
 * When applied to a property in a Laravel Data class, this attribute indicates
 * that the property should be treated as a URL query parameter rather than
 * a request body parameter.
 *
 * On a GET request with no property marked, every property is a query
 * parameter; once one is marked, unmarked properties stay in the body.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class QueryParameter implements DataDocsAttribute {}
