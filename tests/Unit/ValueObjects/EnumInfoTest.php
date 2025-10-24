<?php

use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;

enum EnumInfoTestStringBackedEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

enum EnumInfoTestIntBackedEnum: int
{
    case ONE = 1;
    case TWO = 2;
    case THREE = 3;
}

enum EnumInfoTestUnitEnum
{
    case RED;
    case GREEN;
    case BLUE;
}

it('creates enum info with all properties', function () {
    $cases = EnumInfoTestStringBackedEnum::cases();
    $enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: $cases
    );

    expect($enumInfo->enumType)->toBe(EnumType::STRING_BACKED)
        ->and($enumInfo->cases)->toBe($cases);
});

it('creates enum info with required properties', function () {
    $cases = EnumInfoTestIntBackedEnum::cases();
    $enumInfo = new EnumInfo(
        enumType: EnumType::INT_BACKED,
        cases: $cases
    );

    expect($enumInfo->enumType)->toBe(EnumType::INT_BACKED)
        ->and($enumInfo->cases)->toBe($cases);
});

it('converts to array correctly', function () {
    $cases = EnumInfoTestUnitEnum::cases();
    $enumInfo = new EnumInfo(
        enumType: EnumType::PURE,
        cases: $cases
    );

    $array = $enumInfo->toArray();

    expect($array)->toBe(['RED', 'GREEN', 'BLUE']);
});

it('converts int backed enum to array correctly', function () {
    $cases = EnumInfoTestIntBackedEnum::cases();
    $enumInfo = new EnumInfo(
        enumType: EnumType::INT_BACKED,
        cases: $cases
    );

    $array = $enumInfo->toArray();

    expect($array)->toBe([1, 2, 3]);
});

it('converts string backed enum to array correctly', function () {
    $cases = EnumInfoTestStringBackedEnum::cases();
    $enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: $cases
    );

    $array = $enumInfo->toArray();

    expect($array)->toBe(['active', 'inactive', 'pending']);
});
