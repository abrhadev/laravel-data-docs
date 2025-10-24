<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

enum EnumType
{
    case INT_BACKED;
    case STRING_BACKED;
    case PURE;
}
