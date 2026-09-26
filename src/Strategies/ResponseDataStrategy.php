<?php

namespace Abrha\LaravelDataDocs\Strategies;

use Abrha\LaravelDataDocs\Attributes\ResponseData;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use ReflectionAttribute;
use Spatie\LaravelData\Support\DataConfig;
use Throwable;

class ResponseDataStrategy extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $responseDtoClass = $this->getResponseDtoClass($endpointData);

        if (! $responseDtoClass) {
            return null;
        }

        $parameterGenerator = new ParameterGenerator(
            PipelineFactory::createDefault(config('data-docs', [])),
            app(DataConfig::class),
            outputNames: true,
        );

        return array_map(fn($param) => $param->toArray(), $parameterGenerator($responseDtoClass));
    }

    private function getResponseDtoClass(ExtractedEndpointData $endpointData): ?string
    {
        try {
            $attributes = $endpointData->method?->getAttributes(ResponseData::class, ReflectionAttribute::IS_INSTANCEOF);

            if (empty($attributes)) {
                return null;
            }

            $responseTypeAttribute = $attributes[0]->newInstance();

            return $responseTypeAttribute->dtoClass;
        } catch (Throwable) {
            return null;
        }
    }
}
