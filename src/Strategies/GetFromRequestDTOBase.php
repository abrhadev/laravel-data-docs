<?php

namespace Abrha\LaravelDataDocs\Strategies;

use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterFilter;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Abrha\LaravelDataDocs\Services\RequestDTOFinder;
use Abrha\LaravelDataDocs\ValueObjects\ExtractionStrategy;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Spatie\LaravelData\Support\DataConfig;

class GetFromRequestDTOBase extends Strategy
{
    protected ExtractionStrategy $extractionStrategy;

    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $dtoClass = RequestDTOFinder::getInstance()($endpointData->method);

        if (!$dtoClass) {
            return [];
        }

        $parameterGenerator = new ParameterGenerator(
            PipelineFactory::createDefault(config('data-docs', [])),
            app(DataConfig::class)
        );
        $parameters = $parameterGenerator($dtoClass->getName());

        $targetLocation = $this->extractionStrategy === ExtractionStrategy::QUERY_PARAMETERS
            ? ParameterLocation::QUERY
            : ParameterLocation::BODY;

        $parameterFilter = new ParameterFilter();
        $filteredParameters = $parameterFilter->filterByHttpMethod(
            $parameters,
            $endpointData->httpMethods,
            $targetLocation
        );

        return array_map(fn($param) => $param->toArray(), $filteredParameters);
    }
}
