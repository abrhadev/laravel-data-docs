<?php

namespace Abrha\LaravelDataDocs\Pipeline;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

interface ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext;
}
