<?php

namespace Abrha\LaravelDataDocs\Strategies\QueryParameters;

use Abrha\LaravelDataDocs\Strategies\GetFromRequestDTOBase;
use Abrha\LaravelDataDocs\ValueObjects\ExtractionStrategy;

class GetFromRequestDTOStrategy extends GetFromRequestDTOBase
{
    protected ExtractionStrategy $extractionStrategy = ExtractionStrategy::QUERY_PARAMETERS;
}
