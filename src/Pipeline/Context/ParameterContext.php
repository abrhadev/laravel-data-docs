<?php

namespace Abrha\LaravelDataDocs\Pipeline\Context;

use Abrha\LaravelDataDocs\ValueObjects\ConfirmationCompanion;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\Parameter;
use Abrha\LaravelDataDocs\ValueObjects\ParameterLocation;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Spatie\LaravelData\Support\DataProperty;
use Throwable;

final class ParameterContext
{
    public bool $isHidden = false;

    public bool $hasNestedParameters = false;

    public bool $hasArrayParameters = false;

    public ?string $type = null;

    public ?bool $required = null;

    public ?bool $nullable = null;

    public bool $onlyValidatedWhenPresent = false;

    public bool $presentAcceptsEmpty = false;

    public bool $neverSatisfiable = false;

    public bool $numericString = false;

    /** @var list<string>|null */
    public ?array $allowedValues = null;

    /** @var list<string>|null */
    public ?array $excludedValues = null;

    public ?ConfirmationCompanion $confirmationCompanion = null;

    public ?ParameterLocation $location = null;

    public string $description = '';

    public mixed $example = null;

    public ?EnumInfo $enumInfo = null;

    public ?string $dataClass = null;

    public mixed $default = null;

    public array $descriptions = [];

    public ?string $format = null;

    public ?string $dateFormat = null;

    public ?string $uriScheme = null;

    public ?string $exampleFormat = null;

    public int|float|null $minimum = null;

    public int|float|null $maximum = null;

    public int|float|null $exclusiveMinimum = null;

    public int|float|null $exclusiveMaximum = null;

    public ?string $pattern = null;

    /**
     * The patterns that decide which #[In] values are accepted, one per pattern
     * rule, since Laravel enforces every one while the published pattern holds
     * only the last: the published pattern, or Laravel's own rule where the
     * published pattern only approximates it (the ASCII character classes).
     *
     * @var list<string>
     */
    public array $valuePatterns = [];

    /**
     * Regexes a #[Regex] declares, delimiters and flags included, matched as
     * Laravel runs them. Never published.
     *
     * @var list<string>
     */
    public array $valueRegexes = [];

    /**
     * The ECMA-262 patterns #[Regex] publishes, each the exact translation of
     * a declared regex, so a published pattern among them is not an
     * approximation. Never published separately.
     *
     * @var list<string>
     */
    public array $regexPatterns = [];

    /**
     * Laravel rules of the field's format rules that need no network
     * (email:rfc, uuid, ip, json, url:https, date, a Password rule object),
     * run through Laravel's validator to decide which #[In] values are
     * accepted. Never published.
     *
     * @var list<string|object>
     */
    public array $valueRules = [];

    public ?int $minLength = null;

    public ?int $maxLength = null;

    public ?int $minItems = null;

    public ?int $maxItems = null;

    public ?int $multipleOf = null;

    /**
     * The constraints a custom type sets on each item of an array field,
     * published under items.
     *
     * @var array<string, int|float|string>
     */
    public array $itemSchema = [];

    /**
     * A divisor multipleOf cannot publish (fractional, or negative, as its
     * absolute value): a numeric example is still a multiple of it. Never
     * published.
     */
    public int|float|null $exampleMultipleOf = null;

    public function __construct(
        public readonly string $name,
        public readonly DataProperty $property,
    ) {}

    /**
     * The values an #[In] accepts, typed to the field so the published enum
     * matches the schema type. Laravel compares true as '1' and false as '', so
     * a boolean's '0' passes only as 0 or "0", neither a boolean, and is left
     * out.
     *
     * @return list<int|float|string|bool>|null
     */
    public function allowedValueList(): ?array
    {
        $allowed = $this->acceptedAllowedValues();
        $type = str_replace('[]', '', $this->type ?? '');

        if ($type === 'boolean') {
            $allowed = $allowed === null ? null : array_values(array_filter($allowed, fn(string $value) => $value !== '0'));
        }

        if ($allowed === null || $allowed === []) {
            return null;
        }

        return array_map(fn(string $value) => match ($type) {
            'integer' => (int) $value,
            'number'  => (float) $value,
            'boolean' => $value === '1',
            default   => $value,
        }, $allowed);
    }

    /**
     * The #[In] values the field's other rules also accept: every pattern
     * rule (valuePatterns, valueRegexes), the length bounds of a string, and
     * the numeric bounds and divisor of a number. A value another rule rejects
     * (#[In(['eur', 'USD']), Uppercase], #[In(['ab', 'abcdef']), Max(3)]) is
     * never sent as allowed, while one only the ASCII approximation rejects
     * (in_progress, café) is. A pattern PHP cannot compile filters nothing.
     * Format rules (Email, Uuid, IP) narrow the set through valueRules, except
     * those that need the network (active_url, email:dns).
     *
     * @return list<string>|null
     */
    public function acceptedAllowedValues(): ?array
    {
        if ($this->allowedValues === null) {
            return null;
        }

        return array_values(array_filter(
            $this->allowedValues,
            fn(string $value) => $this->matchesValueRules($value) && $this->withinBounds($value)
        ));
    }

    /**
     * Whether every pattern and format rule of the field accepts the value; a
     * pattern PHP cannot compile, or a rule the validator cannot run, is
     * skipped. A boolean's '1' and '' are its string forms of true and false,
     * so the rules see the boolean itself (declined rejects '' but accepts
     * false).
     */
    public function matchesValueRules(string $value): bool
    {
        $isBoolean = str_replace('[]', '', $this->type ?? '') === 'boolean';
        $typed = match (true) {
            $isBoolean && $value === '1' => true,
            $isBoolean && $value === ''  => false,
            default                      => $value,
        };

        foreach ($this->valuePatterns as $pattern) {
            if (@preg_match("\x01{$pattern}\x01u", $value) === 0) {
                return false;
            }
        }

        foreach ($this->valueRegexes as $regex) {
            if (@preg_match($regex, $value) === 0) {
                return false;
            }
        }

        foreach ($this->valueRules as $rule) {
            try {
                if (app(ValidationFactory::class)->make(['value' => $typed], ['value' => [$rule]])->fails()) {
                    return false;
                }
            } catch (Throwable) {
                continue;
            }
        }

        return true;
    }

    /**
     * Keeps the enum cases $keep accepts. An enum with no case left accepts no
     * value at all, so the field is published with an empty allowed set.
     */
    public function keepEnumCases(callable $keep): void
    {
        if ($this->enumInfo === null) {
            return;
        }

        $cases = array_values(array_filter($this->enumInfo->cases, $keep));

        if ($cases === []) {
            $this->enumInfo = null;
            $this->allowedValues = [];

            return;
        }

        $this->enumInfo = new EnumInfo($this->enumInfo->enumType, $cases);
    }

    /**
     * Whether the field's rules accept the value as acceptedAllowedValues
     * does: its pattern and format rules, its bounds, and the #[In] set when
     * there is one.
     */
    private function acceptsValue(string $value): bool
    {
        return $this->matchesValueRules($value)
            && $this->withinBounds($value)
            && ($this->allowedValues === null || in_array($value, $this->allowedValues, true));
    }

    /**
     * An array field's own bounds count items, not a value, so they narrow
     * nothing; the item constraints a custom type publishes under items
     * (itemSchema) apply to each value.
     */
    private function withinBounds(string $value): bool
    {
        $type = $this->type ?? '';

        if (str_ends_with($type, '[]')) {
            return $this->withinItemSchema($value, str_replace('[]', '', $type));
        }

        if ($type === 'string') {
            $length = mb_strlen($value);

            if (($this->minLength !== null && $length < $this->minLength) || ($this->maxLength !== null && $length > $this->maxLength)) {
                return false;
            }
        }

        if (! in_array($type, ['integer', 'number'], true) && ! ($type === 'string' && $this->numericString)) {
            return true;
        }

        if (! is_numeric($value)) {
            return false;
        }

        $number = (float) $value;
        $isMultiple = fn(int|float|null $divisor) => $divisor === null || abs(round($number / $divisor) * $divisor - $number) < 1e-9;

        return ($this->minimum === null || $number >= $this->minimum)
            && ($this->maximum === null || $number <= $this->maximum)
            && ($this->exclusiveMinimum === null || $number > $this->exclusiveMinimum)
            && ($this->exclusiveMaximum === null || $number < $this->exclusiveMaximum)
            && $isMultiple($this->multipleOf) && $isMultiple($this->exampleMultipleOf);
    }

    private function withinItemSchema(string $value, string $type): bool
    {
        $bound = fn(string $field) => $this->itemSchema[$field] ?? null;

        if ($type === 'string') {
            $length = mb_strlen($value);

            return ($bound('minLength') === null || $length >= $bound('minLength')) && ($bound('maxLength') === null || $length <= $bound('maxLength'));
        }

        if (! in_array($type, ['integer', 'number'], true) || ! is_numeric($value)) {
            return true;
        }

        $number = (float) $value;

        return ($bound('minimum') === null || $number >= $bound('minimum'))
            && ($bound('maximum') === null || $number <= $bound('maximum'))
            && ($bound('exclusiveMinimum') === null || $number > $bound('exclusiveMinimum'))
            && ($bound('exclusiveMaximum') === null || $number < $bound('exclusiveMaximum'))
            && ($bound('multipleOf') === null || abs(round($number / $bound('multipleOf')) * $bound('multipleOf') - $number) < 1e-9);
    }

    /**
     * An #[In] set is exact, so a pattern that rejects one of its values
     * (Lowercase's ASCII approximation beside in_progress) is left out rather
     * than published into a schema no value satisfies. A pattern that only
     * approximates Laravel's rule is also left out when it rejects a value
     * Laravel accepts: on an enum field any case the rules accept (all of
     * them, so the result never depends on the case drawn as the example),
     * otherwise the field's example when the rules accept it (007 beside
     * Digits(3)). The rules are the ones acceptedAllowedValues applies,
     * bounds and the #[In] set included, so an author's #[Example] that
     * Laravel rejects for any of them decides nothing. An exact
     * pattern on an enum field is always kept, since the case list is not
     * narrowed by it.
     *
     * @param list<mixed>|null $enumValues
     */
    private function publishedPattern(?array $enumValues): ?string
    {
        if ($this->pattern === null || @preg_match("\x01{$this->pattern}\x01u", '') === false) {
            return $this->pattern;
        }

        $checked = $enumValues ?? [];

        if ($this->patternIsApproximate()) {
            $accepted = $this->enumInfo !== null ? $this->enumInfo->toArray() : [$this->example];
            array_push($checked, ...array_filter($accepted, fn(mixed $value) => is_string($value) && $this->acceptsValue($value)));
        }

        return $this->patternAdmits($this->pattern, $checked) ? $this->pattern : null;
    }

    /**
     * The item constraints a custom type sets, with its pattern left out when a
     * published #[In] item rejects it, as publishedPattern does for a scalar:
     * a processor's own value patterns may accept an item the published
     * approximation does not.
     *
     * @param  list<mixed>|null  $enumValues
     * @return array<string, int|float|string>|null
     */
    private function publishedItemSchema(?array $enumValues): ?array
    {
        $schema = $this->itemSchema;
        $pattern = $schema['pattern'] ?? null;

        if (is_string($pattern) && @preg_match("\x01{$pattern}\x01u", '') !== false && ! $this->patternAdmits($pattern, $enumValues ?? [])) {
            unset($schema['pattern']);
        }

        return $schema === [] ? null : $schema;
    }

    /**
     * @param  list<mixed>  $values
     */
    private function patternAdmits(string $pattern, array $values): bool
    {
        foreach ($values as $value) {
            if (is_string($value) && preg_match("\x01{$pattern}\x01u", $value) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the published pattern only approximates Laravel's rule (the
     * ASCII character classes): value patterns stand for the rule, and the
     * published one is neither among them nor a declared regex's translation.
     * A pattern with nothing behind it is the rule.
     */
    public function patternIsApproximate(): bool
    {
        return $this->pattern !== null
            && $this->valuePatterns !== []
            && ! in_array($this->pattern, $this->valuePatterns, true)
            && ! in_array($this->pattern, $this->regexPatterns, true);
    }

    public function toParameter(): Parameter
    {
        $numericBound = fn(int|float|null $value) => $this->numericString ? null : $value;
        $enumValues = $this->enumInfo?->toArray() ?? $this->allowedValueList();

        return new Parameter(
            name: $this->name,
            type: $this->type ?? 'string',
            required: $this->required ?? false,
            nullable: $this->nullable ?? true,
            location: $this->location ?? ParameterLocation::BODY,
            description: $this->description,
            example: $this->example,
            enumValues: $enumValues,
            openApiAttributes: array_filter([
                'default'          => $this->default,
                'format'           => $this->format,
                'minimum'          => $numericBound($this->minimum),
                'maximum'          => $numericBound($this->maximum),
                'exclusiveMinimum' => $numericBound($this->exclusiveMinimum),
                'exclusiveMaximum' => $numericBound($this->exclusiveMaximum),
                'pattern'          => $this->publishedPattern($this->enumInfo === null ? $enumValues : null),
                'minLength'        => $this->minLength,
                'maxLength'        => $this->maxLength,
                'minItems'         => $this->minItems,
                'maxItems'         => $this->maxItems,
                'multipleOf'       => $numericBound($this->multipleOf),
                'items'            => $this->publishedItemSchema($this->enumInfo === null ? $enumValues : null),
            ], fn($value) => $value !== null)
        );
    }
}
