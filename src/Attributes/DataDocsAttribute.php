<?php

namespace Abrha\LaravelDataDocs\Attributes;

/**
 * Base interface for all Laravel Data Docs attributes.
 *
 * This interface serves as the foundation for all custom attributes
 * used in Laravel Data documentation generation. Attributes implementing this
 * interface are automatically recognized by the AttributeProcessingStage during
 * the parameter generation pipeline.
 *
 * Custom attributes should implement this interface to be included in the
 * documentation extraction process. The package provides several built-in
 * attributes such as Hidden, Example, QueryParameter, and ResponseData.
 */
interface DataDocsAttribute {}
