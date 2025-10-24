<?php

namespace Abrha\LaravelDataDocs\CustomTypeProcessing;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

interface CustomTypeProcessor
{
    public function process(string $className, ParameterContext $context): void;
}
