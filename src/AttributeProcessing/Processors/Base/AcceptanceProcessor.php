<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

abstract class AcceptanceProcessor extends ConditionProcessor
{
    /**
     * Mirrors Laravel's validateAccepted; pinned by a validator test.
     */
    public const ACCEPTED_VALUES = ['yes', 'on', 1, '1', true, 'true'];

    /**
     * Mirrors Laravel's validateDeclined; pinned by a validator test.
     */
    public const DECLINED_VALUES = ['no', 'off', 0, '0', false, 'false'];

    /**
     * @param array<int, string|int|bool> $values
     */
    protected function valueList(array $values): string
    {
        $tokens = array_map(fn($value) => $this->code(match (true) {
            is_bool($value)                                                 => $value ? 'true' : 'false',
            is_int($value)                                                  => (string) $value,
            is_numeric($value) || in_array($value, ['true', 'false'], true) => '"' . $value . '"',
            default                                                         => $value,
        }), $values);

        $last = array_pop($tokens);

        if ($tokens === []) {
            return $last ?? '';
        }

        return implode(', ', $tokens) . ', or ' . $last;
    }

    protected function applyExample(ParameterContext $context, bool $accepted): void
    {
        if ($context->example !== null || $context->enumInfo !== null) {
            return;
        }

        match ($context->type) {
            'boolean'           => $context->example = $accepted,
            'integer', 'number' => $context->example = $accepted ? 1 : 0,
            'string'            => $context->example = $accepted ? 'yes' : 'no',
            default             => null,
        };
    }
}
