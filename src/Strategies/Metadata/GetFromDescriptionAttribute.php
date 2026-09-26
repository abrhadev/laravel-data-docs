<?php

namespace Abrha\LaravelDataDocs\Strategies\Metadata;

use Abrha\LaravelDataDocs\Attributes\Description;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use ReflectionAttribute;
use Throwable;

class GetFromDescriptionAttribute extends Strategy
{
    public function __invoke(ExtractedEndpointData $endpointData, array $settings = []): ?array
    {
        $description = $this->extractDescription($endpointData);

        if ($description === null) {
            return null;
        }

        return ['description' => $description];
    }

    private function extractDescription(ExtractedEndpointData $endpointData): ?string
    {
        $attributes = $endpointData->method?->getAttributes(Description::class, ReflectionAttribute::IS_INSTANCEOF);

        if (empty($attributes)) {
            return null;
        }

        $parts = [];
        foreach ($attributes as $reflectionAttribute) {
            // One Description that cannot be instantiated drops only itself.
            try {
                $parts = [...$parts, ...$reflectionAttribute->newInstance()->descriptions];
            } catch (Throwable) {
                continue;
            }
        }

        $combined = implode(' ', array_filter($parts));

        return $combined === '' ? null : $combined;
    }
}
