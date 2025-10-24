<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

final class EnumInfo
{
    public function __construct(
        public readonly EnumType $enumType,
        public readonly array $cases
    ) {}

    public function toArray(): array
    {
        return match ($this->enumType) {
            EnumType::PURE => array_map(fn($case) => $case->name, $this->cases),
            EnumType::INT_BACKED, EnumType::STRING_BACKED => array_map(fn($case) => $case->value, $this->cases),
        };
    }
}
