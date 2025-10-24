<?php

namespace Abrha\LaravelDataDocs\Pipeline;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class ParameterPipeline
{
    private array $stages = [];

    public function addStage(ParameterPipelineStage $stage): self
    {
        $this->stages[] = $stage;

        return $this;
    }

    public function process(ParameterContext $context): ParameterContext
    {
        foreach ($this->stages as $stage) {
            $context = $stage->process($context);

            if ($context->isHidden) {
                return $context;
            }
        }

        return $context;
    }
}
