<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

enum ExtractionStrategy: int
{
    case BODY_PARAMETERS = 1;
    case QUERY_PARAMETERS = 2;
}
