<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Faker\Generator;

final class ExampleGenerationStage implements ParameterPipelineStage
{
    public function __construct(
        private readonly Generator $faker
    ) {}

    public function process(ParameterContext $context): ParameterContext
    {
        if ($context->example !== null) {
            return $context;
        }

        if ($context->type === 'object' || $context->type === 'object[]') {
            return $context;
        }

        if ($context->enumInfo !== null && str_ends_with($context->type ?? '', '[]')) {
            $values = $context->enumInfo->toArray();
            if (! empty($values)) {
                $minItems = $context->minItems ?? 1;
                $maxItems = $context->maxItems ?? 3;
                $itemCount = min(max($minItems, 1), min($maxItems, count($values)));
                $context->example = $this->faker->randomElements($values, $itemCount);
            } else {
                $context->example = [];
            }

            return $context;
        }

        if ($context->enumInfo !== null) {
            $values = $context->enumInfo->toArray();
            $context->example = ! empty($values) ? $this->faker->randomElement($values) : null;

            return $context;
        }

        if (str_ends_with($context->type ?? '', '[]')) {
            $context->example = $this->generateArrayExample($context);

            return $context;
        }

        $context->example = match ($context->type) {
            'string'  => $this->generateStringExample($context),
            'integer' => $this->generateNumberExample($context, true),
            'number'  => $this->generateNumberExample($context, false),
            'boolean' => $this->faker->boolean(),
            default   => null,
        };

        return $context;
    }

    private function generateStringExample(ParameterContext $context): string
    {
        if ($context->format) {
            return match ($context->format) {
                'email'     => $this->faker->safeEmail(),
                'url'       => $this->faker->url(),
                'uuid'      => $this->faker->uuid(),
                'ipv4'      => $this->faker->ipv4(),
                'ipv6'      => $this->faker->ipv6(),
                'date'      => $this->faker->date('Y-m-d'),
                'date-time' => $this->faker->iso8601(),
                'time'      => $this->faker->time('H:i:s'),
                'password'  => $this->faker->password(12, 20),
                default     => $this->generateBasicString($context),
            };
        }

        if ($context->pattern) {
            return $this->faker->regexify($context->pattern);
        }

        return $this->generateBasicString($context);
    }

    private function generateBasicString(ParameterContext $context): string
    {
        $minLength = $context->minLength ?? 1;
        $maxLength = $context->maxLength;

        if ($maxLength !== null && $minLength > $maxLength) {
            $minLength = $maxLength;
        }

        if ($maxLength !== null && $maxLength <= 10) {
            return $this->faker->lexify(str_repeat('?', min($maxLength, max($minLength, 3))));
        }

        if ($minLength > 10) {
            $text = $this->faker->text($minLength + 50);

            return substr($text, 0, max($minLength, min(strlen($text), $maxLength ?? $minLength)));
        }

        return $this->faker->word();
    }

    private function generateNumberExample(ParameterContext $context, bool $integer): int|float
    {
        $min = $context->minimum ?? $context->exclusiveMinimum;
        $max = $context->maximum ?? $context->exclusiveMaximum;

        if ($context->exclusiveMinimum !== null) {
            $min = $integer ? $context->exclusiveMinimum + 1 : $context->exclusiveMinimum + 0.1;
        }

        if ($context->exclusiveMaximum !== null) {
            $max = $integer ? $context->exclusiveMaximum - 1 : $context->exclusiveMaximum - 0.1;
        }

        if ($integer) {
            $min = (int) ($min ?? 1);
            $max = (int) ($max ?? 100);
        } else {
            $min = (float) ($min ?? 1.0);
            $max = (float) ($max ?? 100.0);
        }

        if ($min > $max) {
            $max = $min + ($integer ? 100 : 100.0);
        }

        if ($context->multipleOf !== null) {
            $rangeStart = (int) ceil($min / $context->multipleOf);
            $rangeEnd = (int) floor($max / $context->multipleOf);
            $multiplier = $this->faker->numberBetween($rangeStart, $rangeEnd);

            return $integer ? (int) ($multiplier * $context->multipleOf) : (float) ($multiplier * $context->multipleOf);
        }

        if ($integer) {
            return $this->faker->numberBetween((int) ceil($min), (int) floor($max));
        }

        if ($context->maximum === null && $context->exclusiveMaximum === null) {
            $defaultMax = $min + 100.0;

            return $this->faker->randomFloat(2, $min, $defaultMax);
        }

        return $this->faker->randomFloat(2, $min, $max);
    }

    private function generateArrayExample(ParameterContext $context): array
    {
        $baseType = str_replace('[]', '', $context->type ?? '');

        $minItems = $context->minItems ?? 1;
        $maxItems = $context->maxItems ?? 3;

        $itemCount = min(max($minItems, 1), min($maxItems, 3));

        $items = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $items[] = match ($baseType) {
                'string'  => $this->faker->word(),
                'integer' => $this->faker->numberBetween(1, 100),
                'number'  => $this->faker->randomFloat(2, 1, 100),
                'boolean' => $this->faker->boolean(),
                default   => null,
            };
        }

        return $items;
    }
}
