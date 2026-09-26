<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Illuminate\Validation\Rules\Password as PasswordRule;
use ReflectionProperty;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Support\Validation\References\ExternalReference;
use Throwable;

final class PasswordProcessor implements AttributeProcessor
{
    private const GENERIC = 'Must be a valid password.';

    private const ARGUMENTS = ['min', 'letters', 'mixedCase', 'numbers', 'symbols', 'uncompromised', 'uncompromisedThreshold', 'default', 'rule'];

    public function process(object $attribute, ParameterContext $context): void
    {
        $context->format = 'password';

        $rules = $this->appliedRules($attribute);

        if ($rules === null) {
            $context->descriptions[] = self::GENERIC;

            return;
        }

        $min = max((int) $rules['min'], 1);
        $max = is_int($rules['max'] ?? null) ? $rules['max'] : null;

        if ($max !== null && $max < $min) {
            $context->descriptions[] = "Note: must be a password of <code>{$min}</code> to <code>{$max}</code> characters, which no password can be, so any request that sends this field fails validation.";

            return;
        }

        $context->minLength = max($context->minLength ?? $min, $min);

        if ($max !== null) {
            $context->maxLength = min($context->maxLength ?? $max, $max);
        }

        $context->descriptions[] = $this->sentence($rules, $min, $max);
        $context->valueRules[] = $this->valueRule($rules, $min, $max);
    }

    /**
     * The rule the #[In] values are checked against: the applied rules less
     * uncompromised, which needs the network, and less the application's
     * custom rules, which may read the rest of the request (confirmed) or
     * application state.
     *
     * @param array<string, mixed> $rules
     */
    private function valueRule(array $rules, int $min, ?int $max): PasswordRule
    {
        $rule = PasswordRule::min($min);

        if ($max !== null) {
            $rule->max($max);
        }

        foreach (['letters', 'mixedCase', 'numbers', 'symbols'] as $check) {
            if (($rules[$check] ?? false) === true) {
                $rule->{$check}();
            }
        }

        return $rule;
    }

    /**
     * Spatie's Password keeps its constructor arguments protected, and getRule()
     * takes a ValidationPath, which only RequirementResolver may use. The rule
     * getRule() would return is rebuilt from those arguments instead, and read
     * through Laravel's public appliedRules().
     *
     * @return array<string, mixed>|null
     */
    private function appliedRules(object $attribute): ?array
    {
        if (! $attribute instanceof Password) {
            return null;
        }

        try {
            $arguments = [];

            foreach (self::ARGUMENTS as $name) {
                $value = (new ReflectionProperty(Password::class, $name))->getValue($attribute);

                if ($value instanceof ExternalReference) {
                    return null;
                }

                $arguments[$name] = $value;
            }

            return $this->rule($arguments)?->appliedRules();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Mirrors Spatie's Password::getRule().
     *
     * @param array<string, mixed> $arguments
     */
    private function rule(array $arguments): ?PasswordRule
    {
        if ($arguments['rule'] !== null) {
            return $arguments['rule'] instanceof PasswordRule ? $arguments['rule'] : null;
        }

        if ($arguments['default'] === true) {
            return PasswordRule::default();
        }

        $rule = PasswordRule::min((int) $arguments['min']);

        if ($arguments['letters'] === true) {
            $rule->letters();
        }

        if ($arguments['mixedCase'] === true) {
            $rule->mixedCase();
        }

        if ($arguments['numbers'] === true) {
            $rule->numbers();
        }

        if ($arguments['symbols'] === true) {
            $rule->symbols();
        }

        if ($arguments['uncompromised'] === true) {
            $rule->uncompromised((int) $arguments['uncompromisedThreshold']);
        }

        return $rule;
    }

    /**
     * @param array<string, mixed> $rules
     */
    private function sentence(array $rules, int $min, ?int $max): string
    {
        $sentence = $max === null
            ? "Must be a password of at least <code>{$min}</code> " . ($min === 1 ? 'character' : 'characters')
            : "Must be a password of <code>{$min}</code> to <code>{$max}</code> characters";

        $classes = [];

        if (($rules['mixedCase'] ?? false) === true) {
            $classes[] = 'at least one uppercase and one lowercase letter';
        } elseif (($rules['letters'] ?? false) === true) {
            $classes[] = 'at least one letter';
        }

        if (($rules['numbers'] ?? false) === true) {
            $classes[] = 'at least one number';
        }

        if (($rules['symbols'] ?? false) === true) {
            $classes[] = 'at least one symbol';
        }

        if ($classes !== []) {
            $last = array_pop($classes);
            $sentence .= ' containing ' . ($classes === [] ? $last : implode(', ', $classes) . ' and ' . $last);
        }

        $sentence .= '.';

        if (($rules['uncompromised'] ?? false) === true) {
            $threshold = (int) ($rules['compromisedThreshold'] ?? 0);
            $sentence .= $threshold > 0
                ? " Must not appear in known data leaks more than <code>{$threshold}</code> times."
                : ' Must not appear in a known data leak.';
        }

        if (($rules['customRules'] ?? []) !== []) {
            $sentence .= ' Also subject to custom password rules.';
        }

        return $sentence;
    }
}
