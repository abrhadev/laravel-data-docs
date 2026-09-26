<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing;

use ReflectionMethod;
use Spatie\LaravelData\Attributes\Validation\Exclude;
use Spatie\LaravelData\Attributes\Validation\Prohibited;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Support\Validation\RuleNormalizer;
use Spatie\LaravelData\Support\Validation\ValidationRule;
use Throwable;
use WeakMap;

final class ReplacedRules
{
    /** @var WeakMap<Rule, array<int, object>>|null */
    private static ?WeakMap $expansions = null;

    /** @var WeakMap<Exclude, true>|null */
    private static ?WeakMap $synthesised = null;

    /**
     * The declared validation rules as upstream enforces them: each #[Rule] is
     * replaced by the attribute objects it expands to, so a rule string is
     * documented by the processor of the attribute it stands for.
     *
     * @return array<int, object>
     */
    public static function documentedRules(DataProperty $property): array
    {
        $rules = [];

        foreach ($property->attributes->all(ValidationRule::class) as $declared) {
            foreach (self::expand($declared) as $rule) {
                if ($declared instanceof Rule && $rule instanceof Rule) {
                    continue;
                }

                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * Upstream adds each declaration in order, first dropping every collected
     * rule that is an instance of the class of a rule being added. So a later
     * declaration (in practice a #[Rule]) expanding to a rule of this
     * attribute's class replaces it, even when it re-adds an equal rule: the
     * later rule is the one enforced, and it is documented in its own right,
     * so keeping the attribute would publish its sentence twice.
     *
     * Requiring rules also displace one another across classes, and Present
     * strips them; that needs Spatie's RequiringRule, which is confined to
     * RequirementResolver, so the conditional requirement family handles it.
     */
    public static function isReplaced(object $attribute, DataProperty $property): bool
    {
        // An Exclude read from a bare 'exclude' string is a plain Rule
        // upstream, which no later Exclude drops.
        if (isset(self::$synthesised[$attribute])) {
            return false;
        }

        foreach (self::declaredAfter($attribute, $property) as $candidate) {
            foreach (self::expand($candidate) as $rule) {
                if ($attribute instanceof $rule) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The declarations after this one, in the order upstream walks them. A rule
     * expanded from a #[Rule] stands at that Rule's position; an attribute the
     * property does not declare counts as declared first.
     *
     * @return array<int, object>
     */
    public static function declaredAfter(object $attribute, DataProperty $property): array
    {
        $declared = $property->attributes->all(ValidationRule::class);
        $position = array_search($attribute, $declared, true);

        if ($position === false) {
            foreach ($declared as $index => $candidate) {
                if ($candidate instanceof Rule && in_array($attribute, self::expand($candidate), true)) {
                    $position = $index;

                    break;
                }
            }
        }

        return array_slice($declared, $position === false ? 0 : $position + 1);
    }

    /**
     * The rules upstream adds for a declaration: a #[Rule] is expanded the
     * way AttributesRuleInferrer expands it, and one that cannot be expanded
     * counts as nothing. Spatie has no keyword for a bare 'exclude', which
     * stays a Rule upstream yet excludes the field all the same, so it is
     * read as #[Exclude]. The same Rule always gives the same objects, so an
     * expanded rule can be found again by identity.
     *
     * @return array<int, object>
     */
    public static function expand(object $candidate): array
    {
        if (!$candidate instanceof Rule) {
            return [$candidate];
        }

        self::$expansions ??= new WeakMap();

        if (!isset(self::$expansions[$candidate])) {
            try {
                self::$expansions[$candidate] = array_map(
                    fn(object $rule) => self::isBareExclude($rule) ? self::synthesisedExclude() : $rule,
                    array_values(app(RuleNormalizer::class)->execute($candidate))
                );
            } catch (Throwable) {
                self::$expansions[$candidate] = [];
            }
        }

        return self::$expansions[$candidate];
    }

    /**
     * A rule Spatie leaves a plain Rule although Laravel reads it as an
     * exclusion: the bare 'exclude' keyword, for which Spatie has no factory.
     */
    public static function isBareExclude(object $rule): bool
    {
        return $rule instanceof Rule && $rule->get() === ['exclude'];
    }

    /**
     * A Prohibited or Exclude that wraps no rule object, whose condition would
     * otherwise be unreadable. The wrapped rule is read, not evaluated, so a
     * consumer subclass counts as bare as the attribute itself does, and a
     * subclass that never set it counts as bare too. A subclass that declares
     * its own getRule() enforces whatever that returns, so it is never bare.
     */
    public static function isBare(Prohibited|Exclude $attribute): bool
    {
        $base = $attribute instanceof Prohibited ? Prohibited::class : Exclude::class;

        if ((new ReflectionMethod($attribute, 'getRule'))->getDeclaringClass()->getName() !== $base) {
            return false;
        }

        return (fn() => !isset($this->rule))->call($attribute);
    }

    private static function synthesisedExclude(): Exclude
    {
        $exclude = new Exclude();

        self::$synthesised ??= new WeakMap();
        self::$synthesised[$exclude] = true;

        return $exclude;
    }
}
