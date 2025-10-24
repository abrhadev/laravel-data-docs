<?php

namespace Abrha\LaravelDataDocs\Pipeline;

use Abrha\LaravelDataDocs\Pipeline\Stages\AttributeProcessingStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\CustomTypeStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueDescriptionStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\DefaultValueStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\ExampleGenerationStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\HiddenStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\RequiredStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\TypeDescriptionStage;
use Abrha\LaravelDataDocs\Pipeline\Stages\TypeStage;
use Abrha\LaravelDataDocs\ValueObjects\CustomTypeConfig;
use Faker\Factory as FakerFactory;

final class PipelineFactory
{
    public static function createDefault(array $dataDocsConfig = []): ParameterPipeline
    {
        $customTypesConfig = [];
        foreach ($dataDocsConfig['custom_types'] ?? [] as $className => $config) {
            $customTypesConfig[$className] = CustomTypeConfig::fromArray($config);
        }

        $pipeline = new ParameterPipeline();

        return $pipeline
            ->addStage(new HiddenStage())
            ->addStage(new TypeStage())
            ->addStage(new CustomTypeStage($customTypesConfig))
            ->addStage(new AttributeProcessingStage())
            ->addStage(new TypeDescriptionStage())
            ->addStage(new DefaultValueStage())
            ->addStage(new DefaultValueDescriptionStage())
            ->addStage(new RequiredStage())
            ->addStage(new ExampleGenerationStage(FakerFactory::create()));
    }
}
