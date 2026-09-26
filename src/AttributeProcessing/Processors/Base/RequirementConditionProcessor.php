<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithAllProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutAllProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\RequiredWithAll;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;
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
     * The processor that words each conditional attribute's keyword, grouped by
     * the parameters it reads: a field and compared values, or a field list.
     */
    private const SHAPES = [
        [
            RequiredIf::class     => RequiredIfProcessor::class,
            RequiredUnless::class => RequiredUnlessProcessor::class,
        ],
        [
            RequiredWith::class       => RequiredWithProcessor::class,
            RequiredWithAll::class    => RequiredWithAllProcessor::class,
            RequiredWithout::class    => RequiredWithoutProcessor::class,
            RequiredWithoutAll::class => RequiredWithoutAllProcessor::class,
        ],
    ];

    final public function process(object $attribute, ParameterContext $context): void
    {
        $this->describerFor($attribute)?->describe($attribute, $context);
    }

    abstract protected function describe(object $attribute, ParameterContext $context): void;

    /**
     * Upstream builds the enforced rule from keyword(), not from the class the
     * registry found, so a consumer subclass is worded by the keyword it
     * emits: one that switches to a sibling reading the same parameters
     * (RequiredIf to required_unless) gets that sibling's sentence. Any other
     * keyword (plain required, present_if, missing_with, a sibling that would
     * read the parameters differently) or a keyword() that throws gets no
     * sentence, since no template here states the rule Laravel runs.
     */
    private function describerFor(object $attribute): ?self
    {
        foreach (self::SHAPES as $shape) {
            foreach ($shape as $class => $processor) {
                if (!$attribute instanceof $class) {
                    continue;
                }

                try {
                    $keyword = $attribute::keyword();
                } catch (Throwable) {
                    return null;
                }

                foreach ($shape as $sibling => $siblingProcessor) {
                    if ($keyword === $sibling::keyword()) {
                        return $this instanceof $siblingProcessor ? $this : new $siblingProcessor();
                    }
                }

                return null;
            }
        }

        return $this;
    }

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
        foreach ($this->declaredAfter($attribute, $context) as $candidate) {
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

    protected function appendSentence(object $attribute, ParameterContext $context, string $sentence): void
    {
        if ($this->suppressedBy($attribute, $context)) {
            return;
        }

        $context->descriptions[] = $sentence;
    }
}
