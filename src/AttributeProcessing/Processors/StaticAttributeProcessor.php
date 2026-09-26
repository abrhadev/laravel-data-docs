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
        private readonly ?string $exampleFormat = null,
        private readonly ?string $valuePattern = null,
        private readonly ?string $valueRule = null,
    ) {}

    public function process(object $attribute, ParameterContext $context): void
    {
        if ($this->format !== null) {
            $context->format = $this->format;
        }

        if ($this->pattern !== null) {
            $context->pattern = $this->pattern;
            $context->valuePatterns[] = $this->valuePattern ?? $this->pattern;
        }

        if ($this->valueRule !== null) {
            $context->valueRules[] = $this->valueRule;
        }

        if ($this->exampleFormat !== null) {
            $context->exampleFormat = $this->exampleFormat;
        }

        if ($this->description !== '') {
            $context->descriptions[] = $this->description;
        }
    }
}
