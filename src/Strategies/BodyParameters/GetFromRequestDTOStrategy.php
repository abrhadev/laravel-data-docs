<?php

namespace Abrha\LaravelDataDocs\Strategies\BodyParameters;

use Abrha\LaravelDataDocs\Strategies\GetFromRequestDTOBase;
use Abrha\LaravelDataDocs\ValueObjects\ExtractionStrategy;

class GetFromRequestDTOStrategy extends GetFromRequestDTOBase
{
    protected ExtractionStrategy $extractionStrategy = ExtractionStrategy::BODY_PARAMETERS;
}
