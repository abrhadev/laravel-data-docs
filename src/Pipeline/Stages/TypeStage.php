<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use ReflectionEnum;
use Spatie\LaravelData\Enums\DataTypeKind;
use Spatie\LaravelData\Support\Types\NamedType;
use UnitEnum;

final class TypeStage implements ParameterPipelineStage
{
    private const TYPE_MAP = [
        'bool'   => 'boolean',
        'int'    => 'integer',
        'float'  => 'number',
        'string' => 'string',
    ];

    public function process(ParameterContext $context): ParameterContext
    {
        $kind = $context->property->type->kind;
        $context->hasNestedParameters = $kind === DataTypeKind::DataObject;
        $context->hasArrayParameters = $kind === DataTypeKind::DataArray;

        if ($context->hasNestedParameters || $context->hasArrayParameters) {
            $context->type = $context->hasArrayParameters ? 'object[]' : 'object';
            $context->dataClass = $context->property->type->dataClass;

            return $context;
        }

        $type = $context->property->type->type;
        if ($acceptedType = $this->findAcceptedScalarType($type)) {
            $context->type = self::TYPE_MAP[$acceptedType];

            return $context;
        }

        if ($type->acceptsType('array')) {
            $itemType = $context->property->type->iterableItemType;
            $context->type = ($itemType ? (self::TYPE_MAP[$itemType] ?? $itemType) : '') . '[]';
            if ($itemType && enum_exists($itemType)) {
                $this->setEnumInfo($context, $itemType, true);
            }

            return $context;
        }

        if ($enumClass = $type->findAcceptedTypeForBaseType(UnitEnum::class)) {
            $this->setEnumInfo($context, $enumClass, false);

            return $context;
        }

        if ($type instanceof NamedType) {
            $context->type = self::TYPE_MAP[$type->name] ?? $type->name;
        }

        return $context;
    }

    private function findAcceptedScalarType($type): ?string
    {
        foreach (array_keys(self::TYPE_MAP) as $scalarType) {
            if ($type->acceptsType($scalarType)) {
                return $scalarType;
            }
        }

        return null;
    }

    /**
     * The enum's backing type, not its first case, decides the published type,
     * so an enum with no cases is still typed. It accepts no value at all, so
     * the field is published with an empty allowed set.
     */
    private function setEnumInfo(ParameterContext $context, string $enumClass, bool $isArray): void
    {
        $backingTypeName = (new ReflectionEnum($enumClass))->getBackingType()?->getName();
        $baseType = $backingTypeName === null ? 'string' : (self::TYPE_MAP[$backingTypeName] ?? $backingTypeName);
        $context->type = $isArray ? $baseType . '[]' : $baseType;
        $cases = $enumClass::cases();

        if ($cases === []) {
            $context->allowedValues = [];

            return;
        }

        $context->enumInfo = new EnumInfo(
            enumType: match ($backingTypeName) {
                null    => EnumType::PURE,
                'int'   => EnumType::INT_BACKED,
                default => EnumType::STRING_BACKED,
            },
            cases: $cases
        );
    }
}
