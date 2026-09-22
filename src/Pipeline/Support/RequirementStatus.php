<?php

namespace Abrha\LaravelDataDocs\Pipeline\Support;

final class RequirementStatus
{
    public function __construct(
        public readonly bool $required,
        public readonly bool $nullable,
        public readonly bool $onlyValidatedWhenPresent,
    ) {}
}
