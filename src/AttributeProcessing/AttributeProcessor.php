<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

interface AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void;
}
