<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Throwable;

final class EmailProcessor implements AttributeProcessor
{
    private const GENERIC = 'Must be a valid email address.';

    private const CHECKS = [
        'rfc'    => 'RFC 5322 validation',
        'strict' => 'strict RFC 5322 validation, which also rejects addresses with warnings',
        'dns'    => 'a DNS check that its domain has an MX, A or AAAA record',
        'spoof'  => 'a spoofing check that rejects addresses mixing Unicode scripts',
        'filter' => "PHP's <code>FILTER_VALIDATE_EMAIL</code> filter",
    ];

    public function process(object $attribute, ParameterContext $context): void
    {
        $context->format = 'email';
        $context->descriptions[] = $this->sentence($attribute);
        if (($rule = $this->valueRule($attribute)) !== null) {
            $context->valueRules[] = $rule;
        }
    }

    /**
     * The rule the #[In] values are checked against: the declared modes, less
     * dns, which needs the network, and spoof where intl, which it needs, is
     * missing; none when dns is the only mode.
     */
    private function valueRule(object $attribute): ?string
    {
        try {
            $modes = $attribute->parameters();
        } catch (Throwable) {
            return 'email';
        }

        $offline = array_values(array_filter((array) $modes, fn($mode) => is_string($mode) && isset(self::CHECKS[$mode]) && $mode !== 'dns' && ($mode !== 'spoof' || extension_loaded('intl'))));

        if ($offline === []) {
            return in_array('dns', (array) $modes, true) ? null : 'email';
        }

        return 'email:' . implode(',', array_unique($offline));
    }

    /**
     * Spatie's parameters() defaults to rfc, drops unknown modes, and throws when
     * none is left; a documentation build must not fail over one attribute.
     */
    private function sentence(object $attribute): string
    {
        try {
            $modes = $attribute->parameters();
        } catch (Throwable) {
            return self::GENERIC;
        }

        if (! is_array($modes) || array_filter($modes, 'is_string') !== $modes) {
            return self::GENERIC;
        }

        $checks = [];

        foreach (array_unique($modes) as $mode) {
            if (isset(self::CHECKS[$mode])) {
                $checks[] = self::CHECKS[$mode];
            }
        }

        if ($checks === [] || $checks === [self::CHECKS['rfc']]) {
            return self::GENERIC;
        }

        $last = array_pop($checks);
        $list = $checks === [] ? $last : implode(', ', $checks) . ' and ' . $last;

        return "Must be a valid email address that passes {$list}.";
    }
}
