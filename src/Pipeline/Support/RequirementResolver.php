<?php

namespace Abrha\LaravelDataDocs\Pipeline\Support;

use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use ReflectionMethod;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\Exclude;
use Spatie\LaravelData\Attributes\Validation\ExcludeIf;
use Spatie\LaravelData\Attributes\Validation\ExcludeUnless;
use Spatie\LaravelData\Attributes\Validation\ExcludeWith;
use Spatie\LaravelData\Attributes\Validation\ExcludeWithout;
use Spatie\LaravelData\Attributes\Validation\Filled;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Prohibited;
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
 * A subclass of a conditional rule is conditional too while it emits a
 * conditional keyword (see requiringDemands). An unrecognised requiring rule
 * (one implementing RequiringRule directly) counts as unconditional. That
 * preserves the answer given before this distinction existed; guessing
 * optional would risk publishing a mandatory field as optional, the failure
 * this class was written to eliminate.
 *
 * Prohibition never changes requirement status: Prohibited is not a requiring
 * rule, so a non-nullable property without a default still infers Required and
 * is published as required. Such a field can never pass when the key must be
 * sent, the value must be non-empty, and a bare Prohibited demands it be empty,
 * which is what neverSatisfiable states. It reads rejectsEmpty rather than
 * required alone because #[Present, Prohibited] is satisfied by an empty value.
 * Bare means no wrapped rule object, whose condition may make the field
 * satisfiable; ReplacedRules::isBare reads the wrapped rule without evaluating
 * its condition, so a consumer subclass of Prohibited counts too, unless it
 * declares its own getRule(). An
 * exclusion rule earlier in the list also withholds it: Laravel runs rules in
 * this order and stops validating a field once it is excluded, so
 * #[Exclude, Prohibited] passes a request that sends a value. A bare 'exclude'
 * rule string stays a plain Rule in Spatie's list but excludes all the same.
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

    /**
     * Laravel's implicit rules by keyword, with what each enforces in every
     * request: [the key must be sent, an empty value is rejected]. The
     * conditional ones enforce neither, present only the first, filled and
     * missing (which rejects any sent value) only the second.
     * RequirementResolverTest holds this list to Laravel's own.
     */
    private const IMPLICIT_KEYWORDS = [
        'accepted'             => [true, true],
        'accepted_if'          => [false, false],
        'declined'             => [true, true],
        'declined_if'          => [false, false],
        'filled'               => [false, true],
        'missing'              => [false, true],
        'missing_if'           => [false, false],
        'missing_unless'       => [false, false],
        'missing_with'         => [false, false],
        'missing_with_all'     => [false, false],
        'present'              => [true, false],
        'present_if'           => [false, false],
        'present_unless'       => [false, false],
        'present_with'         => [false, false],
        'present_with_all'     => [false, false],
        'required'             => [true, true],
        'required_if'          => [false, false],
        'required_if_accepted' => [false, false],
        'required_if_declined' => [false, false],
        'required_unless'      => [false, false],
        'required_with'        => [false, false],
        'required_with_all'    => [false, false],
        'required_without'     => [false, false],
        'required_without_all' => [false, false],
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

    private const EXCLUDING_RULES = [
        Exclude::class,
        ExcludeIf::class,
        ExcludeUnless::class,
        ExcludeWith::class,
        ExcludeWithout::class,
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

        [$requiringDemandsKey, $requiringRejectsEmpty] = $this->requiringDemands($rules);

        $rejectsEmpty = $requiringRejectsEmpty || $this->rejectsNull($rules);

        $mustBePresent = $requiringDemandsKey
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
            neverSatisfiable: $required
                && $rejectsEmpty
                && $this->hasUnexcludedBareProhibition($rules),
        );
    }

    private function hasUnexcludedBareProhibition(PropertyRules $rules): bool
    {
        foreach ($rules->all() as $rule) {
            if (ReplacedRules::isBareExclude($rule)) {
                return false;
            }

            foreach (self::EXCLUDING_RULES as $excluding) {
                if ($rule instanceof $excluding) {
                    return false;
                }
            }

            if ($rule instanceof Prohibited && ReplacedRules::isBare($rule)) {
                return true;
            }
        }

        return false;
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

    /**
     * What the requiring rules enforce in every request: [the key must be
     * sent, an empty value is rejected].
     *
     * @return array{0: bool, 1: bool}
     */
    private function requiringDemands(PropertyRules $rules): array
    {
        $demandsKey = false;
        $rejectsEmpty = false;

        foreach ($rules->all() as $rule) {
            if (!$rule instanceof RequiringRule) {
                continue;
            }

            [$key, $empty] = $this->demandsOf($rule);
            $demandsKey = $demandsKey || $key;
            $rejectsEmpty = $rejectsEmpty || $empty;
        }

        return [$demandsKey, $rejectsEmpty];
    }

    /**
     * Upstream builds the rule string from keyword(), so the keyword decides
     * what a conditional subclass demands: one that keeps a conditional keyword
     * (it overrides only parameters(), or restates the keyword) or switches to
     * another conditional one, present_if and required_if_accepted included,
     * stays conditional, and one that emits plain required is not. An
     * inherited keyword() is read by reflection without a call. Any other
     * built-in rule is not implicit, so Laravel skips it for an absent key and,
     * beside nullable, for null. A keyword() that throws, or a keyword Laravel
     * does not ship (a custom rule may be implicit), counts as unconditional,
     * as an unrecognised requiring rule does.
     *
     * @return array{0: bool, 1: bool}
     */
    private function demandsOf(RequiringRule $rule): array
    {
        foreach (self::CONDITIONAL_REQUIRING_RULES as $conditional) {
            if (!$rule instanceof $conditional) {
                continue;
            }

            if ((new ReflectionMethod($rule, 'keyword'))->getDeclaringClass()->getName() === $conditional) {
                return self::IMPLICIT_KEYWORDS[$conditional::keyword()];
            }

            try {
                $keyword = $rule::keyword();
            } catch (Throwable) {
                return [true, true];
            }

            return self::IMPLICIT_KEYWORDS[$keyword]
                ?? (method_exists(Validator::class, 'validate' . Str::studly($keyword)) ? [false, false] : [true, true]);
        }

        return [true, true];
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
