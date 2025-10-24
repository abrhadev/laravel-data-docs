<?php

namespace Abrha\LaravelDataDocs\Attributes;

use Attribute;

/**
 * Marks a property as hidden from parameter generation.
 *
 * When applied to a property in a Laravel Data class, this attribute indicates
 * that the property should be completely excluded from the generated parameters.
 * This is useful for:
 * - Internal flags or metadata
 * - Debugging properties
 * - Fields that should not appear in API documentation
 * - Properties processed separately from the main API contract
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Hidden implements DataDocsAttribute {}
