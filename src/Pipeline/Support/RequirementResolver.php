<?php

namespace Abrha\LaravelDataDocs\Pipeline\Support;

use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\Filled;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\RequiredWithAll;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;
use Spatie\LaravelData\Attributes\Validation\Sometimes;
use Spatie\LaravelData\RuleInferrers\RuleInferrer;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Validation\PropertyRules;
use Spatie\LaravelData\Support\Validation\RequiringRule;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Spatie\LaravelData\Support\Validation\ValidationPath;
use Throwable;

/**
 * Sole holder of the coupling to Spatie's Support\Validation namespace.
 *
 * Requirement precedence between a declared type and a validation attribute is
 * inherited from Laravel Data's own rule inferrers rather than restated here, so
 * generated documentation cannot drift from what the API enforces at runtime.
 *
 * The inferrers are run without a payload. Every shipped inferrer accepts a
 * ValidationContext and none reads it; RequirementResolverTest guards that.
 * DataValidationRulesResolver is deliberately not used: it requires a full
 * payload and, given an empty one, omits properties that have a default value.
 *
 * RequiringRule is an empty marker that Laravel Data uses only to decide which
 * rules displace one another, never to decide what a consumer should be told.
 * Seven attributes implement it and six of them are conditional, so asking
 * "does any requiring rule apply?" publishes a field that is required in some
 * requests as required in all of them. Requirement is therefore read from the
 * unconditional rules alone, plus Present, which demands the key be sent.
 *
 * Nullable is not enough to accept null. Required, Accepted, Declined and Filled
 * are implicit rules, so Laravel still runs them against a null value and
 * rejects it; a property carrying any of them is published as not nullable.
 * Present is implicit too but accepts null, and the conditional implicit rules
 * reject it only while their condition holds, so neither subtracts. The PHP
 * type must admit null as well: #[Present, Nullable] string keeps a Nullable
 * rule once Present strips the inferred Required, yet Laravel Data cannot
 * construct the property from null.
 *
 * The same rules reject an empty value, so Present only means "send the key,
 * it may be empty" when none of them is in force alongside it. Even then only a
 * nullable, uncast string survives an empty value end to end: Laravel's default
 * ConvertEmptyStringsToNull turns '' into null, and any other type fails when
 * Laravel Data casts or constructs it.
 *
 * Accepted and Declined also run when the key is absent and fail, so like
 * Present they demand the key without being requiring rules. Filled does not:
 * it passes when the key is absent.
 *
 * An unrecognised requiring rule counts as unconditional. That preserves the
 * answer given before this distinction existed; guessing optional would risk
 * publishing a mandatory field as optional, the failure this class was written
 * to eliminate.
 */
final class RequirementResolver
{
    private const CONDITIONAL_REQUIRING_RULES = [
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
    ];

    private const KEY_DEMANDING_RULES = [
        Present::class,
        Accepted::class,
        Declined::class,
    ];

    private const NULL_REJECTING_RULES = [
        Accepted::class,
        Declined::class,
        Filled::class,
    ];

    /**
     * @param array<int, RuleInferrer> $inferrers
     */
    public function __construct(
        private readonly array $inferrers,
    ) {}

    public static function fromConfig(): self
    {
        return new self(app(DataConfig::class)->ruleInferrers);
    }

    /**
     * Returns null when the property could not be reconciled, which asks the
     * caller to fall back rather than fail the documentation build.
     */
    public function resolve(DataProperty $property): ?RequirementStatus
    {
        if ($this->inferrers === []) {
            // No inferrers means nothing can be reconciled. Answering "not required,
            // not nullable" here would be worse than the type-derived baseline, so
            // hand back to the caller's fallback instead.
            return null;
        }

        try {
            $rules = $this->inferRules($property);
        } catch (Throwable) {
            return null;
        }

        $onlyValidatedWhenPresent = $rules->hasType(Sometimes::class);

        $unconditionallyRequired = $this->hasUnconditionalRequirement($rules);

        $rejectsEmpty = $unconditionallyRequired || $this->rejectsNull($rules);

        $mustBePresent = $unconditionallyRequired
            || $this->hasAnyType($rules, self::KEY_DEMANDING_RULES);

        $nullable = $rules->hasType(Nullable::class)
            && $property->type->isNullable
            && !$rejectsEmpty;

        $required = $mustBePresent
            && !$onlyValidatedWhenPresent
            && !$property->hasDefaultValue;

        return new RequirementStatus(
            required: $required,
            nullable: $nullable,
            onlyValidatedWhenPresent: $onlyValidatedWhenPresent,
            presentAcceptsEmpty: $required
                && $nullable
                && $rules->hasType(Present::class)
                && $this->isPlainString($property),
        );
    }

    private function isPlainString(DataProperty $property): bool
    {
        return array_keys($property->type->getAcceptedTypes()) === ['string']
            && $property->cast === null;
    }

    private function rejectsNull(PropertyRules $rules): bool
    {
        return $this->hasAnyType($rules, self::NULL_REJECTING_RULES);
    }

    /**
     * @param array<int, class-string> $classes
     */
    private function hasAnyType(PropertyRules $rules, array $classes): bool
    {
        foreach ($classes as $class) {
            if ($rules->hasType($class)) {
                return true;
            }
        }

        return false;
    }

    private function hasUnconditionalRequirement(PropertyRules $rules): bool
    {
        foreach ($rules->all() as $rule) {
            if (!$rule instanceof RequiringRule) {
                continue;
            }

            if (in_array($rule::class, self::CONDITIONAL_REQUIRING_RULES, true)) {
                continue;
            }

            return true;
        }

        return false;
    }

    private function inferRules(DataProperty $property): PropertyRules
    {
        $rules = new PropertyRules();
        $context = new ValidationContext(null, null, ValidationPath::create());

        foreach ($this->inferrers as $inferrer) {
            $rules = $inferrer->handle($property, $rules, $context);
        }

        return $rules;
    }
}
