<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class StaticAttributeProcessor implements AttributeProcessor
{
    public function __construct(
        private readonly ?string $format = null,
        private readonly ?string $pattern = null,
        private readonly string $description = '',
    ) {}

    public function process(object $attribute, ParameterContext $context): void
    {
        if ($this->format !== null) {
            $context->format = $this->format;
        }

        if ($this->pattern !== null) {
            $context->pattern = $this->pattern;
        }

        if ($this->description !== '') {
            $context->descriptions[] = $this->description;
        }
    }
}
