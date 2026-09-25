<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\RequiredWithAll;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Support\Validation\RuleNormalizer;
use Spatie\LaravelData\Support\Validation\ValidationRule;
use Throwable;

abstract class RequirementConditionProcessor extends ConditionProcessor
{
    /**
     * Declared attributes Laravel Data treats as requiring rules: adding one
     * replaces every requiring rule already collected for the property. Listed
     * by class because RequiringRule itself is confined to RequirementResolver.
     */
    private const REQUIRING_ATTRIBUTES = [
        Required::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
    ];

    /**
     * Upstream walks the declared validation attributes in order. A later
     * requiring attribute replaces this condition, and a later #[Present] strips
     * it, so in either case the condition is not enforced. A condition declared
     * after both is still enforced and keeps its sentence. An attribute not
     * declared on the property is treated as declared first.
     *
     * #[Rule('required')] reaches upstream as the rules it expands to, so it is
     * expanded the same way. It is never tested as Present: upstream checks the
     * declared attribute itself, and a Rule is not one.
     */
    protected function suppressedBy(object $attribute, ParameterContext $context): bool
    {
        $declared = $context->property->attributes->all(ValidationRule::class);
        $position = array_search($attribute, $declared, true);

        foreach (array_slice($declared, $position === false ? 0 : $position + 1) as $candidate) {
            if ($candidate instanceof Present) {
                return true;
            }

            foreach ($this->expand($candidate) as $rule) {
                foreach (self::REQUIRING_ATTRIBUTES as $requiring) {
                    if ($rule instanceof $requiring) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @return array<int, object>
     */
    private function expand(object $candidate): array
    {
        if (!$candidate instanceof Rule) {
            return [$candidate];
        }

        try {
            return app(RuleNormalizer::class)->execute($candidate);
        } catch (Throwable) {
            return [];
        }
    }

    protected function appendSentence(object $attribute, ParameterContext $context, string $sentence): void
    {
        if ($this->suppressedBy($attribute, $context)) {
            return;
        }

        $context->descriptions[] = $sentence;
    }
}
