<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipeline;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Spatie\LaravelData\Support\DataProperty;

it('processes context through single stage', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);

    $stage = mock(ParameterPipelineStage::class);
    $stage->shouldReceive('process')
        ->once()
        ->with($context)
        ->andReturn($context);

    $pipeline = new ParameterPipeline();
    $pipeline->addStage($stage);

    $result = $pipeline->process($context);

    expect($result)->toBe($context);
});

it('processes context through multiple stages in order', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);

    $stage1 = new class implements ParameterPipelineStage {
        public function process(ParameterContext $context): ParameterContext
        {
            $context->type = 'string';

            return $context;
        }
    };

    $stage2 = new class implements ParameterPipelineStage {
        public function process(ParameterContext $context): ParameterContext
        {
            $context->required = true;

            return $context;
        }
    };

    $stage3 = new class implements ParameterPipelineStage {
        public function process(ParameterContext $context): ParameterContext
        {
            $context->description = 'Test description';

            return $context;
        }
    };

    $pipeline = new ParameterPipeline();
    $pipeline->addStage($stage1)
        ->addStage($stage2)
        ->addStage($stage3);

    $result = $pipeline->process($context);

    expect($result->type)->toBe('string')
        ->and($result->required)->toBeTrue()
        ->and($result->description)->toBe('Test description');
});

it('stops processing after stage marks context as hidden', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);

    $stage1 = new class implements ParameterPipelineStage {
        public function process(ParameterContext $context): ParameterContext
        {
            $context->type = 'string';

            return $context;
        }
    };

    $stage2 = new class implements ParameterPipelineStage {
        public function process(ParameterContext $context): ParameterContext
        {
            $context->isHidden = true;

            return $context;
        }
    };

    $stage3 = new class implements ParameterPipelineStage {
        public function process(ParameterContext $context): ParameterContext
        {
            $context->description = 'Should not be set';

            return $context;
        }
    };

    $pipeline = new ParameterPipeline();
    $pipeline->addStage($stage1)
        ->addStage($stage2)
        ->addStage($stage3);

    $result = $pipeline->process($context);

    expect($result->type)->toBe('string')
        ->and($result->isHidden)->toBeTrue()
        ->and($result->description)->toBe('');
});

it('returns same context when no stages are added', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);

    $pipeline = new ParameterPipeline();
    $result = $pipeline->process($context);

    expect($result)->toBe($context);
});

it('allows chaining addStage calls', function () {
    $stage1 = mock(ParameterPipelineStage::class);
    $stage2 = mock(ParameterPipelineStage::class);
    $stage3 = mock(ParameterPipelineStage::class);

    $pipeline = new ParameterPipeline();
    $result = $pipeline->addStage($stage1)
        ->addStage($stage2)
        ->addStage($stage3);

    expect($result)->toBeInstanceOf(ParameterPipeline::class);
});

it('early exits when context is hidden before any stage runs', function () {
    $property = mock(DataProperty::class);
    $context = new ParameterContext('test', $property);
    $context->isHidden = true;

    $stage = mock(ParameterPipelineStage::class);
    $stage->shouldReceive('process')
        ->once()
        ->with($context)
        ->andReturn($context);

    $pipeline = new ParameterPipeline();
    $pipeline->addStage($stage);

    $result = $pipeline->process($context);

    expect($result->isHidden)->toBeTrue();
});
