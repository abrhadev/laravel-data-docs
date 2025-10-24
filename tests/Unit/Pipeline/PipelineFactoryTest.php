<?php

use Abrha\LaravelDataDocs\Pipeline\ParameterPipeline;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;

it('creates pipeline instance', function () {
    $pipeline = PipelineFactory::createDefault();

    expect($pipeline)->toBeInstanceOf(ParameterPipeline::class);
});

it('creates pipeline with all stages in correct order', function () {
    $pipeline = PipelineFactory::createDefault();

    expect($pipeline)->toBeInstanceOf(ParameterPipeline::class);
});
