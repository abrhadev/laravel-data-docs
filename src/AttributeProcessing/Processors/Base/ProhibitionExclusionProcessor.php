<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base;

use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

abstract class ProhibitionExclusionProcessor extends ConditionProcessor
{
    protected function appendEnforced(object $attribute, ParameterContext $context, string $sentence): void
    {
        if ($this->replacedLater($attribute, $context)) {
            return;
        }

        $context->descriptions[] = $sentence;
    }

    /**
     * Upstream adds each declaration in order, first dropping every collected
     * rule of the class of a rule being added. So a later declaration (in
     * practice a #[Rule]) expanding to a rule of this attribute's class
     * replaces it, even when that declaration re-adds an equal rule, which is
     * then documented in the attribute's place.
     *
     * Only the attribute's own class counts: requiring rules displace only one
     * another, and Present strips only requiring rules, so neither ever
     * disables a prohibition or exclusion.
     */
    private function replacedLater(object $attribute, ParameterContext $context): bool
    {
        return ReplacedRules::isReplaced($attribute, $context->property);
    }
}
