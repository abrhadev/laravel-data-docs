<?php

namespace Abrha\LaravelDataDocs\Pipeline\Support;

use Spatie\LaravelData\Attributes\Validation\Nullable;
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
 */
final class RequirementResolver
{
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

        return new RequirementStatus(
            required: $rules->hasType(RequiringRule::class)
                && !$onlyValidatedWhenPresent
                && !$property->hasDefaultValue,
            nullable: $rules->hasType(Nullable::class),
            onlyValidatedWhenPresent: $onlyValidatedWhenPresent,
        );
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
