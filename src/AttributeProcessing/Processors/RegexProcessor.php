<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing\Processors;

use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

/**
 * OpenAPI's `pattern` is an ECMA-262 regex without delimiters or flags. A declared
 * PHP regex is translated to it only where both dialects read it the same way;
 * otherwise no pattern is published, because a wrong one would make a validating
 * client reject values Laravel accepts. The sentence always quotes the regex as
 * declared.
 */
final class RegexProcessor implements AttributeProcessor
{
    private const BRACKET_DELIMITERS = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'];

    private const KEPT_MODIFIERS = 'DSXUru';

    private const KEPT_ESCAPES = 'dDwWsSbBfnrt';

    private const SYNTAX_CHARACTERS = '^$\\.*+?()[]{}|/';

    private const GENERAL_CATEGORIES = [
        'L', 'Lu', 'Ll', 'Lt', 'Lm', 'Lo', 'M', 'Mn', 'Mc', 'Me', 'N', 'Nd', 'Nl', 'No',
        'P', 'Pc', 'Pd', 'Ps', 'Pe', 'Pi', 'Pf', 'Po', 'S', 'Sm', 'Sc', 'Sk', 'So',
        'Z', 'Zs', 'Zl', 'Zp', 'C', 'Cc', 'Cf', 'Cs', 'Co', 'Cn',
    ];

    public function process(object $attribute, ParameterContext $context): void
    {
        $parameters = $attribute->parameters();
        $regex = $parameters[0] ?? null;

        if (! is_string($regex)) {
            return;
        }

        $pattern = $this->toEcmaPattern($regex);

        // Laravel runs the regex as declared, flags included, so it decides accepted values exactly.
        if (@preg_match($regex, '') !== false) {
            $context->valueRegexes[] = $regex;
        }

        if ($pattern !== null) {
            $context->pattern = $pattern;
            $context->regexPatterns[] = $pattern;
        }

        $context->descriptions[] = "Must match the regex <code>{$regex}</code>.";
    }

    private function toEcmaPattern(string $regex): ?string
    {
        if (@preg_match($regex, '') === false) {
            return null;
        }

        $regex = ltrim($regex, " \t\n\r\v\f");
        $close = self::BRACKET_DELIMITERS[$regex[0]] ?? $regex[0];
        $end = strrpos($regex, $close);

        if ($end === false || $end === 0) {
            return null;
        }

        $modifiers = str_replace([' ', "\t", "\n", "\r", "\v", "\f"], '', substr($regex, $end + 1));
        $anchored = false;

        foreach (str_split($modifiers) as $modifier) {
            if ($modifier === 'A') {
                $anchored = true;
            } elseif (! str_contains(self::KEPT_MODIFIERS . 'n', $modifier)) {
                return null;
            }
        }

        // With u, PCRE reads \w, \d and \b as Unicode classes, which ECMA-262
        // keeps ASCII even in its own u mode, so the pattern would reject café.
        if (str_contains($modifiers, 'u') && preg_match('/(?<!\\\\)(?:\\\\\\\\)*\\\\[wWdDbB]/', substr($regex, 1, $end - 1)) === 1) {
            return null;
        }

        // A reference to a group that did not take part matches the empty
        // string in ECMA-262 but fails in PCRE, so a backreference could make
        // the pattern accept values Laravel rejects.
        $hasBackreference = false;
        $body = $this->translateBody(substr($regex, 1, $end - 1), $hasBackreference);

        if ($body === null || $hasBackreference) {
            return null;
        }

        return $anchored ? "^(?:{$body})" : $body;
    }

    private function translateBody(string $body, bool &$hasBackreference): ?string
    {
        $translated = '';
        $inClass = false;
        $afterQuantifier = false;
        $afterAssertion = false;
        $assertionGroups = [];

        for ($i = 0; $i < strlen($body); $i++) {
            $char = $body[$i];
            $quantifier = false;
            $closedAssertion = false;

            // PCRE accepts a quantified lookaround; ECMA-262 rejects it.
            if ($afterAssertion && ! $inClass && ($char === '*' || $char === '+' || $char === '?' || ($char === '{' && preg_match('/^\{\d+(?:,\d*)?\}/', substr($body, $i)) === 1))) {
                return null;
            }

            if ($char === '\\') {
                $escape = $this->translateEscape(substr($body, $i + 1), $inClass, $hasBackreference);

                if ($escape === null) {
                    return null;
                }

                [$text, $consumed] = $escape;
                $translated .= $text;
                $i += $consumed;
            } elseif ($inClass) {
                if ($char === '[' && in_array($body[$i + 1] ?? '', [':', '.', '='], true)) {
                    return null;
                }

                $translated .= $char === '[' ? '\\[' : $char;
                $inClass = $char !== ']';
            } elseif ($char === '[') {
                $inClass = true;
                $translated .= '[';

                if (($body[$i + 1] ?? '') === '^') {
                    $translated .= '^';
                    $i++;
                }

                if (($body[$i + 1] ?? '') === ']') {
                    $translated .= '\\]';
                    $i++;
                }
            } elseif ($char === '(') {
                $opening = $this->groupOpening(substr($body, $i));

                if ($opening === null) {
                    return null;
                }

                $translated .= $opening;
                $i += strlen($opening) - 1;
                $assertionGroups[] = in_array($opening, ['(?=', '(?!', '(?<=', '(?<!'], true);
            } elseif ($char === ')') {
                $translated .= ')';
                $closedAssertion = array_pop($assertionGroups) === true;
            } elseif ($char === '*' || $char === '+' || $char === '?') {
                if ($afterQuantifier && $char !== '?') {
                    return null;
                }

                $translated .= $char;
                $quantifier = ! $afterQuantifier;
            } elseif ($char === '{') {
                $rest = substr($body, $i);

                if (preg_match('/^\{\d+(?:,\d*)?\}/', $rest, $match) === 1) {
                    $translated .= $match[0];
                    $i += strlen($match[0]) - 1;
                    $quantifier = true;
                } elseif (preg_match('/^\{\s*(?:\d+\s*(?:,\s*\d*\s*)?|,\s*\d+\s*)\}/', $rest) === 1) {
                    return null;
                } else {
                    $translated .= '\\{';
                }
            } elseif ($char === '}' || $char === ']') {
                $translated .= '\\' . $char;
            } else {
                $translated .= $char;
            }

            $afterQuantifier = $quantifier;
            $afterAssertion = $closedAssertion;
        }

        return $translated;
    }

    /**
     * The ECMA-262 spelling of the escape whose backslash precedes `$rest`, and
     * how many characters of `$rest` it consumes; null for a PCRE-only escape.
     *
     * @return array{0: string, 1: int}|null
     */
    private function translateEscape(string $rest, bool $inClass, bool &$hasBackreference): ?array
    {
        $next = $rest[0] ?? '';

        if ($next === '') {
            return null;
        }

        if (! ctype_alnum($next)) {
            $keepsBackslash = str_contains(self::SYNTAX_CHARACTERS, $next) || ($inClass && $next === '-');

            return [$keepsBackslash ? '\\' . $next : $next, 1];
        }

        if (str_contains(self::KEPT_ESCAPES, $next)) {
            return ['\\' . $next, 1];
        }

        if (! $inClass && preg_match('/^[1-9](?!\d)/', $rest) === 1) {
            $hasBackreference = true;

            return ['\\' . $next, 1];
        }

        if (preg_match('/^k<[A-Za-z_]\w*>/', $rest, $match) === 1) {
            $hasBackreference = true;

            return ['\\' . $match[0], strlen($match[0])];
        }

        if (preg_match('/^(?:0(?!\d)|x[0-9a-fA-F]{2}|c[A-Za-z])/', $rest, $match) === 1) {
            return ['\\' . $match[0], strlen($match[0])];
        }

        if (preg_match('/^[pP]\{(\w+)\}/', $rest, $match) === 1 && in_array($match[1], self::GENERAL_CATEGORIES, true)) {
            return ['\\' . $match[0], strlen($match[0])];
        }

        return null;
    }

    private function groupOpening(string $rest): ?string
    {
        if (str_starts_with($rest, '(*')) {
            return null;
        }

        if (! str_starts_with($rest, '(?')) {
            return '(';
        }

        return preg_match('/^\(\?(?::|=|!|<=|<!|<[A-Za-z_]\w*>)/', $rest, $match) === 1 ? $match[0] : null;
    }
}
