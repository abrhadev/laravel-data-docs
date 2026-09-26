<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

final class UrlProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $context->format = 'uri';

        $protocols = $attribute->parameters();

        if ($protocols === [] || array_filter($protocols, 'is_string') !== $protocols) {
            $context->descriptions[] = 'Must be a valid URL.';
            $context->valueRules[] = 'url';

            return;
        }

        $rendered = array_values(array_map(fn(string $protocol) => "<code>{$protocol}</code>", array_unique($protocols)));

        $context->descriptions[] = count($rendered) === 1
            ? "Must be a valid URL using the {$rendered[0]} protocol."
            : 'Must be a valid URL using one of the protocols: ' . implode(', ', $rendered) . '.';

        $context->valueRules[] = 'url:' . implode(',', array_unique($protocols));
        $this->recordScheme($context, $protocols);
    }

    /**
     * ExampleGenerationStage generates the uri example, an https URL unless a
     * scheme is recorded, so the field's other rules still shape it. Laravel
     * matches the protocol list case-insensitively, as a regex alternation
     * inserted unescaped, so a protocol holding another regex metacharacter
     * records no scheme.
     *
     * @param array<int, string> $protocols
     */
    private function recordScheme(ParameterContext $context, array $protocols): void
    {
        if ($context->type !== 'string') {
            return;
        }

        foreach ($protocols as $protocol) {
            if (strcasecmp($protocol, 'https') === 0) {
                return;
            }
        }

        foreach ($protocols as $protocol) {
            if (preg_match('/^[A-Za-z][A-Za-z0-9.-]*$/', $protocol) === 1) {
                $context->uriScheme = $protocol;

                return;
            }
        }
    }
}
