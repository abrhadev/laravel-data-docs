<?php

use Abrha\LaravelDataDocs\Pipeline\ParameterPipeline;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Pipeline\Stages\AttributeProcessingStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\CustomTypeStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueDescriptionStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\ExampleGenerationStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\HiddenStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\RequiredStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\RequirementDescriptionStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\TypeDescriptionStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\TypeStage;
use Abrha\LaravelDataDocs\ValueObjects\CustomTypeConfig;

function pipelineStageClasses(ParameterPipeline $pipeline): array
{
    $stages = (new ReflectionProperty(ParameterPipeline::class, 'stages'))->getValue($pipeline);

    return array_map(fn(object $stage) => $stage::class, $stages);
}

it('creates pipeline instance', function () {
    expect(PipelineFactory::createDefault())->toBeInstanceOf(ParameterPipeline::class);
});

it('creates pipeline with all stages in correct order', function () {
    expect(pipelineStageClasses(PipelineFactory::createDefault()))->toBe([
        HiddenStage::class,
        TypeStage::class,
        CustomTypeStage::class,
        AttributeProcessingStage::class,
        TypeDescriptionStage::class,
        DefaultValueStage::class,
        DefaultValueDescriptionStage::class,
        RequiredStage::class,
        RequirementDescriptionStage::class,
        ExampleGenerationStage::class,
    ]);
});

it('places the requirement description immediately after the requirement stage', function () {
    $classes = pipelineStageClasses(PipelineFactory::createDefault());

    expect(array_search(RequirementDescriptionStage::class, $classes, true))
        ->toBe(array_search(RequiredStage::class, $classes, true) + 1);
});

it('leaves example generation last so it sees the resolved requirement status', function () {
    $classes = pipelineStageClasses(PipelineFactory::createDefault());

    expect(end($classes))->toBe(ExampleGenerationStage::class);
});

it('passes configured custom types to the custom type stage', function () {
    $pipeline = PipelineFactory::createDefault([
        'custom_types' => [
            'App\\Money' => ['type' => 'string', 'descriptions' => ['Must be a money amount.']],
        ],
    ]);

    $stages = (new ReflectionProperty(ParameterPipeline::class, 'stages'))->getValue($pipeline);
    $customTypeStage = array_values(array_filter($stages, fn(object $stage) => $stage instanceof CustomTypeStage))[0];
    $config = (new ReflectionProperty(CustomTypeStage::class, 'customTypesConfig'))->getValue($customTypeStage);

    expect($config)->toHaveKey('App\\Money')
        ->and($config['App\\Money'])->toBeInstanceOf(CustomTypeConfig::class)
        ->and($config['App\\Money']->descriptions)->toBe(['Must be a money amount.']);
});
