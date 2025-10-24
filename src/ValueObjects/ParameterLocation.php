<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

enum ParameterLocation: string
{
    case QUERY = 'query';
    case BODY = 'body';
}
