<?php

namespace Abrha\LaravelDataDocs\Services;

use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;

final class ParameterFilter
{
    public function filterByHttpMethod(array $parameters, array $httpMethods, ParameterLocation $targetLocation): array
    {
        if (count($httpMethods) === 1 && $httpMethods[0] === 'GET') {
            return $this->filterForGetRequest($parameters, $targetLocation);
        }

        return $this->filterByLocation($parameters, $targetLocation);
    }

    private function filterForGetRequest(array $parameters, ParameterLocation $targetLocation): array
    {
        $hasQueryFields = $this->hasParametersWithLocation($parameters, ParameterLocation::QUERY);

        if (!$hasQueryFields) {
            return $targetLocation === ParameterLocation::BODY ? [] : $parameters;
        }

        return $this->filterByLocation($parameters, $targetLocation);
    }

    private function filterByLocation(array $parameters, ParameterLocation $location): array
    {
        return array_filter(
            $parameters,
            fn(Parameter $parameter) => $parameter->matchesLocation($location)
        );
    }

    private function hasParametersWithLocation(array $parameters, ParameterLocation $location): bool
    {
        foreach ($parameters as $parameter) {
            if ($parameter instanceof Parameter && $parameter->matchesLocation($location)) {
                return true;
            }
        }

        return false;
    }
}
