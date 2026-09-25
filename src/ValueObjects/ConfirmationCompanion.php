<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

/**
 * Recorded by ConfirmedProcessor and turned into a published parameter by ParameterGenerator.
 */
final readonly class ConfirmationCompanion
{
    public function __construct(
        public string $name,
        public string $matchSentence,
        public string $requiredWhenSentSentence,
    ) {}
}
