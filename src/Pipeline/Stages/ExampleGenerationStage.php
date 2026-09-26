<?php

namespace Abrha\LaravelDataDocs\Pipeline\Stages;

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Closure;
use DateTime;
use DateTimeZone;
use Faker\Generator;
use InvalidArgumentException;
use Throwable;

final class ExampleGenerationStage implements ParameterPipelineStage
{
    private const PATTERN_ATTEMPTS = 200;

    public function __construct(
        private readonly Generator $faker
    ) {}

    private const EXCLUDED_REDRAWS = 20;

    /**
     * The drawable characters of each class drawFrom has read, which depend on
     * the class alone.
     *
     * @var array<string, list<string>>
     */
    private static array $classMembers = [];

    public function process(ParameterContext $context): ParameterContext
    {
        if ($context->example !== null) {
            return $context;
        }

        $this->generate($context);

        for ($redraw = 0; $redraw < self::EXCLUDED_REDRAWS && $this->isExcluded($context); $redraw++) {
            $context->example = null;
            $this->generate($context);
        }

        return $context;
    }

    /**
     * An #[NotIn] rejects a value whose string form it lists, and an array
     * only when all of its items are listed, which an empty array vacuously is.
     */
    private function isExcluded(ParameterContext $context): bool
    {
        if (!$context->excludedValues || $context->example === null) {
            return false;
        }

        $items = is_array($context->example) ? $context->example : [$context->example];

        foreach ($items as $item) {
            $form = is_bool($item) ? ($item ? '1' : '') : (is_scalar($item) ? (string) $item : null);

            if (!in_array($form, $context->excludedValues, true)) {
                return false;
            }
        }

        return true;
    }

    private function generate(ParameterContext $context): ParameterContext
    {
        if ($context->type === 'object' || $context->type === 'object[]') {
            return $context;
        }

        if ($context->enumInfo !== null && str_ends_with($context->type ?? '', '[]')) {
            $values = $context->enumInfo->toArray();
            if (! empty($values)) {
                $context->example = $this->itemsFrom($values, $this->itemCount($context));
            } else {
                $context->example = [];
            }

            return $context;
        }

        if ($context->enumInfo !== null) {
            $values = $context->enumInfo->toArray();
            $context->example = ! empty($values) ? $this->faker->randomElement($values) : null;

            return $context;
        }

        if (($allowed = $context->allowedValueList()) !== null) {
            $context->example = str_ends_with($context->type ?? '', '[]')
                ? $this->itemsFrom($allowed, $this->itemCount($context))
                : $this->faker->randomElement($allowed);

            return $context;
        }

        // #[In([0])] on a boolean: only 0 passes, which the published list cannot hold.
        if (str_replace('[]', '', $context->type ?? '') === 'boolean' && in_array('0', $context->acceptedAllowedValues() ?? [], true)) {
            $context->example = str_ends_with($context->type ?? '', '[]') ? [0] : 0;

            return $context;
        }

        if (str_ends_with($context->type ?? '', '[]')) {
            $context->example = $this->generateArrayExample($context);

            return $context;
        }

        if ($context->type === 'string' && $context->numericString) {
            $context->example = $this->generateNumericStringExample($context);

            return $context;
        }

        $context->example = match ($context->type) {
            'string'  => $this->generateStringExample($context),
            'integer' => $this->generateNumberExample($context, true),
            'number'  => $this->generateNumberExample($context, false),
            'boolean' => $this->faker->boolean(),
            default   => null,
        };

        return $context;
    }

    private function generateStringExample(ParameterContext $context, bool $honourPattern = true): string
    {
        $format = $context->format ?? $context->exampleFormat;

        if ($honourPattern && ($format || $context->dateFormat !== null) && ($context->pattern || $context->valueRegexes !== [])) {
            return $this->generateFormatAndPatternExample($context, $context->pattern);
        }

        if ($context->dateFormat !== null) {
            return $this->generateDateFormatExample($context->dateFormat, $context->exampleFormat === 'date');
        }

        if ($format) {
            return match ($format) {
                'email'     => $this->faker->safeEmail(),
                'url'       => $this->faker->url(),
                'uri'       => $this->generateUriExample($context),
                'json'      => (string) json_encode($this->faker->boolean() ? [$this->faker->word() => $this->faker->word()] : [$this->faker->word()]),
                'uuid'      => $this->faker->uuid(),
                'ipv4'      => $this->faker->ipv4(),
                'ipv6'      => $this->faker->ipv6(),
                'date'      => $this->faker->dateTimeBetween('-50 years', '+50 years', 'UTC')->format('Y-m-d'),
                'date-time' => $this->faker->dateTimeBetween('-50 years', '+50 years', 'UTC')->format(DATE_ATOM),
                'time'      => $this->faker->time('H:i:s'),
                'password'  => $this->generatePasswordExample($context),
                default     => $this->generateBasicString($context),
            };
        }

        if ($context->pattern || $context->valueRegexes !== []) {
            return $this->generatePatternExample($context);
        }

        return $this->generateBasicString($context);
    }

    /**
     * An example every pattern rule and the length bounds accept. Each pattern
     * is a source to draw from, and draws of two sources are also joined or
     * laid over each other, since one rule's draw often fails another
     * (#[StartsWith('img_'), EndsWith('.png')]). Faker's regexify ignores the
     * length, so a candidate is fitted to the bounds (fittedToLength), and it
     * is kept only when Laravel's pattern rules all accept it (valuePatterns
     * and valueRegexes, which the published pattern may only approximate).
     */
    private function generatePatternExample(ParameterContext $context): string
    {
        $compilable = fn(?string $pattern) => $pattern !== null && @preg_match("\x01{$pattern}\x01u", '') !== false;
        $checks = $context->valuePatterns === [] && $context->valueRegexes === [] ? array_filter([$context->pattern], $compilable) : [];
        $sources = array_values(array_unique(array_filter([$context->pattern, ...$context->valuePatterns, ...array_map(fn(string $regex) => $this->regexBody($regex), $context->valueRegexes)], $compilable)));

        $published = array_values(array_filter([$context->pattern], $compilable));
        $fallback = null;

        for ($attempt = 0; $attempt < self::PATTERN_ATTEMPTS / 10; $attempt++) {
            $draws = array_values(array_filter(array_map(fn(string $source) => $this->drawFrom($source), $sources), fn(?string $draw) => $draw !== null));

            // A regex declared with i, or a case rule beside a mixed-case pattern (Ulid), wants a case the draw does not know.
            $draws = array_values(array_unique([...$draws, ...array_map('strtolower', $draws), ...array_map('strtoupper', $draws)]));

            foreach ($this->combinedDraws($draws) as $draw) {
                foreach ($this->fittedToLength($draw, $context) as $candidate) {
                    // Laravel skips a non-required rule for an empty value, and TrimStrings would strip an edge space.
                    if ($candidate === '' || trim($candidate) !== $candidate || ! $context->matchesValueRules($candidate) || ! $this->matchesAll($candidate, $checks)) {
                        continue;
                    }

                    // One the published pattern also matches keeps the document consistent with itself.
                    if ($this->matchesAll($candidate, $published)) {
                        return $candidate;
                    }

                    $fallback ??= $candidate;
                }
            }
        }

        if ($fallback !== null) {
            return $fallback;
        }

        // No fitted draw met every rule: a basic string or a plain draw may, else a draw at least keeps the published pattern.
        $drawn = $context->pattern !== null ? $this->drawFrom($context->pattern) : null;
        $basic = $this->generateBasicString($context);

        foreach ([$drawn, $basic] as $candidate) {
            if ($candidate !== null && $candidate !== '' && $context->matchesValueRules($candidate) && $this->matchesAll($candidate, [...$checks, ...$published])) {
                return $candidate;
            }
        }

        return $drawn !== null && $this->matchesAll($drawn, $published) ? $drawn : $basic;
    }

    /**
     * A declared regex without its delimiters and flags, and without the
     * PCRE-only anchors (\A, \z, \Z) regexify cannot read, to draw from; the
     * regex itself still decides whether a draw is kept. An x flag's
     * insignificant whitespace and # comments are also removed, since the
     * reader has no x-mode of its own.
     */
    private function regexBody(string $regex): ?string
    {
        $regex = ltrim($regex);
        $close = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'][$regex[0] ?? ''] ?? ($regex[0] ?? '');
        $end = $close === '' ? false : strrpos($regex, $close);

        if ($end === false || $end === 0) {
            return null;
        }

        $body = (string) preg_replace('/(?<!\\\\)((?:\\\\\\\\)*)\\\\[AzZ]/', '$1', substr($regex, 1, $end - 1));

        return str_contains(substr($regex, $end + 1), 'x') ? $this->stripExtendedWhitespace($body) : $body;
    }

    /**
     * Under PCRE's x modifier, an unescaped whitespace character outside a
     * class is insignificant, and an unescaped # outside a class starts a
     * comment that runs to the next newline; both are removed here so the
     * reader, which has no x-mode of its own, never sees them.
     */
    private function stripExtendedWhitespace(string $body): string
    {
        $stripped = '';
        $position = 0;
        $length = strlen($body);

        while ($position < $length) {
            if (preg_match('/\G\\\\./su', $body, $escape, 0, $position) === 1) {
                $stripped .= $escape[0];
                $position += strlen($escape[0]);

                continue;
            }

            if (preg_match('/\G\[\^?\]?(?:\[:\^?[a-z]+:\]|\\\\.|[^\]\\\\])*\]/s', $body, $class, 0, $position) === 1) {
                $stripped .= $class[0];
                $position += strlen($class[0]);

                continue;
            }

            if (preg_match('/\G(?:\s+|#[^\n]*)/', $body, $skipped, 0, $position) === 1) {
                $position += strlen($skipped[0]);

                continue;
            }

            if (preg_match('/\G./su', $body, $literal, 0, $position) !== 1) {
                break;
            }

            $stripped .= $literal[0];
            $position += strlen($literal[0]);
        }

        return $stripped;
    }

    /**
     * A value drawn from a pattern by a small PCRE reader of its own, since
     * Faker's regexify reads only a plain subset and writes the rest out as
     * text ({4,} or (?=.*\d) literally). A class, an escape (\d, \W, \p{L},
     * \x41) or a dot is drawn from the printable characters it matches,
     * letters and digits first and a symbol where the class wants one ([^\w]
     * as #); an open-ended count runs up to five past its minimum;
     * lookarounds, anchors, word boundaries and inline flags are dropped; a
     * backreference repeats its group. The draw only shapes the candidate:
     * the field's rules still decide whether it is kept. Null when the
     * pattern holds a construct the reader does not know.
     */
    private function drawFrom(string $pattern): ?string
    {
        try {
            $position = 0;
            $groups = 0;
            $captures = [];
            $draw = $this->readAlternation($pattern, $position, $groups, $captures);

            return $position === strlen($pattern) ? $draw() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, string>  $captures
     * @return Closure(): string
     */
    private function readAlternation(string $pattern, int &$position, int &$groups, array &$captures): Closure
    {
        $branches = [$this->readSequence($pattern, $position, $groups, $captures)];

        while (($pattern[$position] ?? '') === '|') {
            $position++;
            $branches[] = $this->readSequence($pattern, $position, $groups, $captures);
        }

        return fn() => $this->faker->randomElement($branches)();
    }

    /**
     * @param  array<int, string>  $captures
     * @return Closure(): string
     */
    private function readSequence(string $pattern, int &$position, int &$groups, array &$captures): Closure
    {
        $items = [];

        while ($position < strlen($pattern) && $pattern[$position] !== '|' && $pattern[$position] !== ')') {
            $atom = $this->readAtom($pattern, $position, $groups, $captures);

            if (preg_match('/\G(?:([?*+])|\{(\d*)(,?)(\d*)\})[?+]?/', $pattern, $quantifier, 0, $position) !== 1
                || ($quantifier[1] === '' && $quantifier[2] === '' && $quantifier[4] === '')) {
                $items[] = $atom;

                continue;
            }

            $position += strlen($quantifier[0]);
            [$least, $most] = match ($quantifier[1]) {
                '?'     => [0, 1],
                '*'     => [0, 5],
                '+'     => [1, 6],
                default => [(int) $quantifier[2], match (true) {
                    $quantifier[3] === '' => (int) $quantifier[2],
                    $quantifier[4] === '' => (int) $quantifier[2] + 5,
                    default               => (int) $quantifier[4],
                }],
            };

            if ($least > $most) {
                throw new InvalidArgumentException('A quantifier whose minimum exceeds its maximum.');
            }

            $items[] = function () use ($atom, $least, $most): string {
                $draw = '';

                for ($count = $this->faker->numberBetween($least, $most); $count > 0; $count--) {
                    $draw .= $atom();
                }

                return $draw;
            };
        }

        return fn() => implode('', array_map(fn(Closure $item) => $item(), $items));
    }

    /**
     * @param  array<int, string>  $captures
     * @return Closure(): string
     */
    private function readAtom(string $pattern, int &$position, int &$groups, array &$captures): Closure
    {
        $empty = fn() => '';

        switch ($pattern[$position]) {
            case '(':
                return $this->readGroup($pattern, $position, $groups, $captures);
            case '[':
                if (preg_match('/\G\[\^?\]?(?:\[:\^?[a-z]+:\]|\\\\.|[^\]\\\\])*\]/s', $pattern, $class, 0, $position) !== 1) {
                    throw new InvalidArgumentException('An unclosed class.');
                }

                $position += strlen($class[0]);

                return $this->oneOf($class[0]);
            case '.':
                $position++;

                return $this->oneOf('.');
            case '^':
            case '$':
                $position++;

                return $empty;
            case '\\':
                return $this->readEscape($pattern, $position, $captures);
            case '*':
            case '+':
            case '?':
                throw new InvalidArgumentException('A quantifier with nothing to repeat.');
        }

        if (preg_match('/\G./su', $pattern, $literal, 0, $position) !== 1) {
            throw new InvalidArgumentException('Not UTF-8.');
        }

        $position += strlen($literal[0]);

        return fn() => $literal[0];
    }

    /**
     * @param  array<int, string>  $captures
     * @return Closure(): string
     */
    private function readGroup(string $pattern, int &$position, int &$groups, array &$captures): Closure
    {
        $empty = fn() => '';
        $capture = null;
        $zeroWidth = false;

        if (preg_match('/\G\((?:\?#[^)]*|\?[a-zA-Z^-]+)\)/', $pattern, $skipped, 0, $position) === 1) {
            // A comment or an inline flag, (?i), matches no text; the case copies cover the flag.
            $position += strlen($skipped[0]);

            return $empty;
        }

        if (preg_match('/\G\((?:\?P?<[A-Za-z_]\w*>|\?\'[A-Za-z_]\w*\'|(?!\?))/', $pattern, $opening, 0, $position) === 1) {
            $capture = ++$groups;
        } elseif (preg_match('/\G\(\?(?:=|!|<=|<!)/', $pattern, $opening, 0, $position) === 1) {
            $zeroWidth = true;
        } elseif (preg_match('/\G\(\?(?:[:>|]|[a-zA-Z^-]+:)/', $pattern, $opening, 0, $position) !== 1) {
            throw new InvalidArgumentException('A group the reader does not know.');
        }

        $position += strlen($opening[0]);
        $inner = $this->readAlternation($pattern, $position, $groups, $captures);

        if (($pattern[$position] ?? '') !== ')') {
            throw new InvalidArgumentException('An unclosed group.');
        }

        $position++;

        return match (true) {
            // A lookaround asserts without matching text; the field's rules decide whether the draw meets it.
            $zeroWidth        => $empty,
            $capture !== null => function () use (&$captures, $capture, $inner): string {
                return $captures[$capture] = $inner();
            },
            default => $inner,
        };
    }

    /**
     * @param  array<int, string>  $captures
     * @return Closure(): string
     */
    private function readEscape(string $pattern, int &$position, array &$captures): Closure
    {
        $empty = fn() => '';
        $next = $pattern[$position + 1] ?? throw new InvalidArgumentException('A trailing backslash.');

        if (preg_match('/\G\\\\(?:[AzZbBGK]|Q(.*?)(?:\\\\E|$)|([1-9]\d*)|x(?:\{([0-9a-fA-F]+)\}|([0-9a-fA-F]{0,2}))|([pP](?:\{\^?[A-Za-z&_ ]+\}|[A-Za-z])|[dDwWsShHvVNR]))/s', $pattern, $escape, 0, $position) === 1) {
            $position += strlen($escape[0]);

            return match (true) {
                ($escape[1] ?? '') !== '' => fn() => $escape[1],
                ($escape[2] ?? '') !== '' => function () use (&$captures, $escape): string {
                    return $captures[(int) $escape[2]] ?? '';
                },
                ($escape[3] ?? '') !== '' || str_starts_with($escape[0], '\x') => fn() => (string) mb_chr((int) hexdec(($escape[3] ?? '') . ($escape[4] ?? ''))),
                ($escape[5] ?? '') !== ''                                      => $this->oneOf($escape[0]),
                default                                                        => $empty,
            };
        }

        $controls = ['t' => "\t", 'n' => "\n", 'r' => "\r", 'f' => "\f", 'e' => "\e", 'a' => "\x07"];

        if (isset($controls[$next])) {
            $position += 2;

            return fn() => $controls[$next];
        }

        if (ctype_alnum($next) || preg_match('/\G\\\\(.)/su', $pattern, $literal, 0, $position) !== 1) {
            throw new InvalidArgumentException('An escape the reader does not know.');
        }

        $position += strlen($literal[0]);

        return fn() => $literal[1];
    }

    /**
     * A draw of one character a class, an escape or a dot matches.
     *
     * @return Closure(): string
     */
    private function oneOf(string $class): Closure
    {
        $members = self::$classMembers[$class] ??= $this->membersOf($class);

        if ($members === []) {
            throw new InvalidArgumentException('A class no drawable character matches.');
        }

        return fn() => $this->faker->randomElement($members);
    }

    /**
     * The printable characters (ASCII, Latin, Greek and Cyrillic) a class
     * matches: its ASCII letters and digits when it holds any, else its ASCII
     * symbols (a regex without u counts bytes), else its other letters, else a
     * space or a tab.
     *
     * @return list<string>
     */
    private function membersOf(string $class): array
    {
        $matching = fn(array $characters) => array_values(array_filter(
            $characters,
            fn(string $character) => @preg_match("\x01^{$class}$\x01u", $character) === 1
        ));

        $members = $matching(array_map(fn(int $code) => (string) mb_chr($code), [...range(0x21, 0x7E), ...range(0xA1, 0x24F), ...range(0x370, 0x3FF), ...range(0x400, 0x4FF)]));
        $plain = array_values(array_filter($members, fn(string $character) => ctype_alnum($character)));
        $ascii = array_values(array_filter($members, fn(string $character) => strlen($character) === 1));

        return $plain !== [] ? $plain : ($ascii !== [] ? $ascii : ($members !== [] ? $members : $matching([' ', "\t"])));
    }

    /**
     * Each draw, then every two draws joined in either order and laid over the
     * start or the end of each other.
     *
     * @param  list<string>  $draws
     * @return list<string>
     */
    private function combinedDraws(array $draws): array
    {
        $combined = $draws;

        foreach ($draws as $i => $first) {
            foreach ($draws as $j => $second) {
                if ($i === $j || $first === '' || $second === '') {
                    continue;
                }

                $combined[] = $first . $second;

                if (strlen($first) <= strlen($second)) {
                    $combined[] = $first . substr($second, strlen($first));
                    $combined[] = substr($second, 0, -strlen($first)) . $first;
                }
            }
        }

        return array_values(array_unique($combined));
    }

    /**
     * @return list<string>
     */
    private function fittedToLength(string $value, ParameterContext $context): array
    {
        $length = mb_strlen($value);
        $min = $context->minLength ?? 0;
        $max = $context->maxLength;

        if ($max !== null && $min > $max) {
            return [$value];
        }

        if ($length < $min) {
            $middle = intdiv($length, 2);

            // Padding in the middle keeps both a prefix and a suffix intact.
            return $value === '' ? [] : [
                $value . str_repeat(mb_substr($value, -1), $min - $length),
                str_repeat(mb_substr($value, 0, 1), $min - $length) . $value,
                mb_substr($value, 0, $middle) . str_repeat(mb_substr($value, max($middle - 1, 0), 1), $min - $length) . mb_substr($value, $middle),
            ];
        }

        if ($max !== null && $length > $max) {
            return $max === 0 ? [''] : [mb_substr($value, 0, $max), mb_substr($value, -$max)];
        }

        return [$value];
    }

    /**
     * @param list<string> $patterns
     */
    private function matchesAll(string $value, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match("\x01{$pattern}\x01u", $value) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Dates are drawn from fifty years either side of today, so a pattern on the
     * field (StartsWith('2030')) can be met by some draw. With a #[Date] beside
     * the format, the value must also pass date, which reads d/m/Y as m/d/Y.
     */
    private function generateDateFormatExample(string $format, bool $passesDate = false): string
    {
        $utc = new DateTimeZone('UTC');

        $draws = 0;

        do {
            $value = $this->faker->dateTimeBetween('-50 years', '+50 years', 'UTC')->format($format);
            $parsed = DateTime::createFromFormat('!' . $format, $value, $utc);
            $valid = $parsed !== false && $parsed->format($format) == $value && (! $passesDate || $this->passesDateRule($value));
        } while (! $valid && ++$draws < 50);

        return $value;
    }

    /**
     * Laravel's date rule: strtotime reads the value and date_parse finds a
     * calendar date in it.
     */
    private function passesDateRule(string $value): bool
    {
        if (strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return is_int($date['month']) && is_int($date['day']) && is_int($date['year']) && checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * The hosts resolve, so an active_url rule passes too. A maximum length
     * drops the path, which gives the shortest URL on those hosts; a minimum
     * length pads the path.
     */
    private function generateUriExample(ParameterContext $context): string
    {
        $url = ($context->uriScheme ?? 'https') . '://' . $this->faker->safeEmailDomain() . '/';

        if ($context->maxLength === null) {
            $url .= $this->faker->slug(2);
        }

        if ($context->minLength !== null && strlen($url) < $context->minLength) {
            $url .= $this->faker->lexify(str_repeat('?', $context->minLength - strlen($url)));
        }

        return $url;
    }

    /**
     * Holds every character class Laravel's Password rule can require, so it
     * passes whichever of them a field asks for, within the length bounds.
     */
    private function generatePasswordExample(ParameterContext $context): string
    {
        $lower = max($context->minLength ?? 1, 12);
        $upper = max($lower, 20);

        if ($context->maxLength !== null) {
            $upper = min($upper, $context->maxLength);
            $lower = min($lower, $upper);
        }

        $length = $this->faker->numberBetween($lower, $upper);

        $required = $this->faker->randomElement(range('a', 'z'))
            . $this->faker->randomElement(range('A', 'Z'))
            . $this->faker->randomDigit()
            . $this->faker->randomElement(str_split('!#$%&*+-=?@^_'));

        $rest = $length > 4 ? $this->faker->asciify(str_repeat('*', $length - 4)) : '';

        return substr($this->faker->shuffleString($required . $rest), 0, max($length, 0));
    }

    /**
     * JSON Schema always asserts `pattern` but treats `format` as an annotation by
     * default, so a format example is kept only when it matches the pattern and
     * every other rule of the field (the other pattern rules, the format's own
     * rule, a date's reading). When no draw does, the pattern rules' own
     * examples are laid over one, and the pattern example is the fallback.
     */
    private function generateFormatAndPatternExample(ParameterContext $context, ?string $pattern): string
    {
        $date = $context->dateFormat !== null || in_array($context->format ?? $context->exampleFormat, ['date', 'date-time'], true);

        // An approximate pattern (Lowercase's ASCII class beside an email) need not match: Laravel's own rule decides.
        $accepts = fn(string $example) => ($pattern === null || $context->patternIsApproximate() || @preg_match("\x01{$pattern}\x01u", $example) === 1)
            && $context->matchesValueRules($example)
            && (! $date || $this->isDateExample($example, $context));

        for ($attempt = 0; $attempt < self::PATTERN_ATTEMPTS; $attempt++) {
            $example = $this->generateStringExample($context, false);

            // Faker draws one case (a lower-case UUID); a case rule beside the format may want the other.
            foreach (array_unique([$example, strtolower($example), strtoupper($example)]) as $candidate) {
                if ($accepts($candidate)) {
                    return $candidate;
                }
            }
        }

        return $this->overlaidExample($context, $pattern, $accepts) ?? $this->generatePatternExample($context);
    }

    /**
     * A narrow pattern (StartsWith('2031-05'), EndsWith('.pdf'), StartsWith('10.'))
     * matches few format draws, so each pattern rule's own example is laid over
     * the start or the end of a drawn one, a character or two either side of
     * its length (10. over 192.168.1.5 gives 10.168.1.5), keeping the rules
     * already met.
     */
    private function overlaidExample(ParameterContext $context, ?string $pattern, callable $accepts): ?string
    {
        // An approximate pattern (Lowercase's ASCII class beside a URL) is no source: an overlay need not match it, as $accepts does not.
        $sources = array_values(array_unique(array_filter(
            [$context->patternIsApproximate() ? null : $pattern, ...$context->valuePatterns, ...array_map(fn(string $regex) => $this->regexBody($regex), $context->valueRegexes)],
            fn(?string $source) => $source !== null && @preg_match("\x01{$source}\x01u", '') !== false
        )));

        for ($attempt = 0; $attempt < self::PATTERN_ATTEMPTS; $attempt++) {
            $candidate = $this->generateStringExample($context, false);
            $met = [];

            foreach ($sources as $source) {
                if (preg_match("\x01{$source}\x01u", $candidate) !== 1 && ($piece = $this->drawFrom($source)) !== null && $piece !== '') {
                    $matching = null;

                    // An overlay the whole field accepts wins; one that only meets the rules so far is a step towards it.
                    foreach ($this->overlays($candidate, $piece) as $option) {
                        if ($this->matchesAll($option, [...$met, $source])) {
                            if ($accepts($option)) {
                                $matching = $option;

                                break;
                            }

                            $matching ??= $option;
                        }
                    }

                    $candidate = $matching ?? $candidate;
                }

                $met[] = $source;
            }

            if ($accepts($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * The piece over the start or the end of the value, replacing about as
     * many characters as it has first, then up to or from each separator: a
     * domain (@corp.com) must replace the whole drawn one (@example.com),
     * which starts at the @.
     *
     * @return list<string>
     */
    private function overlays(string $value, string $piece): array
    {
        $options = [];

        foreach ([0, 1, -1, 2, -2] as $shift) {
            $cut = strlen($piece) + $shift;

            if ($cut < 0 || $cut > strlen($value)) {
                continue;
            }

            $options[] = $piece . substr($value, $cut);
            $options[] = substr($value, 0, strlen($value) - $cut) . $piece;
        }

        foreach (str_split($value) as $offset => $character) {
            if (! ctype_alnum($character)) {
                $options[] = $piece . substr($value, $offset);
                $options[] = substr($value, 0, $offset) . $piece;
            }
        }

        return array_values(array_unique($options));
    }

    private function isDateExample(string $value, ParameterContext $context): bool
    {
        if ($context->dateFormat !== null) {
            $parsed = DateTime::createFromFormat('!' . $context->dateFormat, $value, new DateTimeZone('UTC'));

            if ($parsed === false || $parsed->format($context->dateFormat) != $value) {
                return false;
            }
        }

        return ($context->dateFormat !== null && $context->exampleFormat !== 'date') || $this->passesDateRule($value);
    }

    private function generateBasicString(ParameterContext $context): string
    {
        $minLength = $context->minLength ?? 1;
        $maxLength = $context->maxLength;

        if ($maxLength !== null && $minLength > $maxLength) {
            $minLength = $maxLength;
        }

        if ($maxLength !== null && $maxLength <= 10) {
            return $this->faker->lexify(str_repeat('?', min($maxLength, max($minLength, 3))));
        }

        if ($minLength > 10) {
            $text = $this->faker->text($minLength + 50);

            // Faker's text() often falls short of the length it is asked for.
            if (strlen($text) < $minLength) {
                $text .= $this->faker->lexify(str_repeat('?', $minLength - strlen($text)));
            }

            $text = substr($text, 0, max($minLength, min(strlen($text), $maxLength ?? $minLength)));

            // Laravel's TrimStrings middleware trims input before validation, so an edge space would cost a character.
            return (string) preg_replace_callback('/^\s|\s$/', fn() => $this->faker->randomLetter(), $text);
        }

        $word = $this->faker->word();

        if (strlen($word) < $minLength) {
            $word .= $this->faker->lexify(str_repeat('?', $minLength - strlen($word)));
        }

        return $maxLength === null ? $word : substr($word, 0, $maxLength);
    }

    /**
     * Laravel compares a numeric string's comparison bounds as a number but its
     * Min, Max, Between and Size as length, so the example honours both, and
     * the field's pattern rules (a \d regex, a digit count, a prefix): each
     * draw is also tried padded with more leading zeros (035 for ^\d{3}$), and
     * with no upper bound over ranges ten to a thousand times as wide (1005
     * for ^[1-9]\d{3}$).
     */
    private function generateNumericStringExample(ParameterContext $context): string
    {
        [$lower, $upper] = $this->numericRange($context, true);

        $divisor = $this->wholeDivisor($context);

        // A range holding no whole number (#[GreaterThan(0.5), LessThan(0.9)]) still holds decimals, and so do the multiples of a fractional divisor.
        if (($lower !== null && $upper !== null && $lower > $upper) || ($divisor === null && $context->exampleMultipleOf !== null)) {
            return $this->decimalNumericString($context);
        }

        $minLength = $context->minLength ?? 0;
        $unbounded = $upper === null;

        if ($minLength > 1) {
            $natural = 10 ** (min($minLength, 18) - 1);

            if ($upper === null || $upper >= $natural) {
                $lower = max($lower ?? $natural, $natural);
            }
        }

        // The length cap comes first, so a divisor above it (#[MultipleOf(1000), Max(3)]) leaves 0 as the multiple.
        $cap = $context->maxLength !== null ? 10 ** min($context->maxLength, 18) - 1 : null;
        $upper = $cap !== null ? min($upper ?? $cap, $cap) : $upper;

        $lower ??= match (true) {
            $upper === null                        => 1,
            $divisor !== null && $upper < $divisor => $upper >= 0 ? 0 : $upper - 100 * $divisor,
            default                                => min(1, $upper),
        };
        $upper ??= $lower > PHP_INT_MAX - 99 ? PHP_INT_MAX : $lower + max(99, 10 * ($divisor ?? 1));

        if ($context->maxLength !== null) {
            $lower = max($lower, $context->maxLength > 1 ? -(10 ** min($context->maxLength - 1, 18) - 1) : 0);
        }

        $tops = [$upper];

        for ($scale = 10; $unbounded && $scale <= 1000 && $upper <= intdiv(PHP_INT_MAX, $scale); $scale *= 10) {
            $tops[] = $cap !== null ? min($upper * $scale, $cap) : $upper * $scale;
        }

        $first = null;

        for ($draw = 0; $draw < self::PATTERN_ATTEMPTS / 2; $draw++) {
            $top = $tops[$draw % count($tops)];
            $value = $lower > $top ? $lower : $this->faker->numberBetween($lower, $top);

            // A whole divisor keeps the example a whole multiple, and 0 is one when the range holds no other.
            if ($divisor !== null) {
                if (($from = $this->ceilDiv($lower, $divisor)) <= ($to = $this->floorDiv($top, $divisor))) {
                    $value = $this->faker->numberBetween($from, $to) * $divisor;
                } elseif ($this->withinNumericBounds($context, 0)) {
                    $value = 0;
                }
            }

            $sign = $value < 0 ? '-' : '';
            $digits = (string) abs($value);
            $width = max($minLength - strlen($sign), strlen($digits));
            $widest = $context->maxLength !== null ? $context->maxLength - strlen($sign) : $width + 3;

            // Leading zeros pad it as they pad any number, and change neither its value nor a multiple.
            for (; $width <= max($widest, strlen($digits)); $width++) {
                $form = $sign . str_pad($digits, $width, '0', STR_PAD_LEFT);
                $first ??= $form;

                if ($context->matchesValueRules($form)) {
                    return $form;
                }
            }
        }

        return (string) $first;
    }

    /**
     * The whole number every divisor of a numeric string divides (the least
     * common multiple of multipleOf and a whole exampleMultipleOf), or null
     * when there is none or a divisor is fractional.
     */
    private function wholeDivisor(ParameterContext $context): ?int
    {
        $divisors = array_filter([$context->multipleOf, $context->exampleMultipleOf], fn(int|float|null $divisor) => $divisor !== null);

        if ($divisors === [] || array_filter($divisors, fn(int|float $divisor) => floor($divisor) != $divisor || $divisor >= PHP_INT_MAX) !== []) {
            return null;
        }

        return array_reduce($divisors, function (?int $carry, int|float $divisor) {
            $divisor = (int) $divisor;

            if ($carry === null) {
                return $divisor;
            }

            [$a, $b] = [$carry, $divisor];

            while ($b !== 0) {
                [$a, $b] = [$b, $a % $b];
            }

            return intdiv($carry, $a) * $divisor;
        });
    }

    /**
     * A decimal within the bounds, written to the field's length bounds, which
     * Laravel applies to the string: trailing zeros pad it (0.7000), leading
     * zeros pad a whole one (033), and fewer decimals shorten it while it stays
     * in range. The draw is capped by the length (below 10 for Max(1)), and a
     * form is kept only when the field's pattern rules accept it too.
     */
    private function decimalNumericString(ParameterContext $context): string
    {
        $range = clone $context;

        if ($context->maxLength !== null) {
            $floor = $context->maxLength > 1 ? -(10 ** min($context->maxLength - 1, 18) - 1) : 0;
            $range->maximum = min($context->maximum ?? INF, 10 ** min($context->maxLength, 18) - 1);

            if ($context->minimum !== null || $context->exclusiveMinimum !== null || $range->maximum <= 0) {
                $range->minimum = max($context->minimum ?? $floor, $floor);
            }
        }

        $first = null;

        for ($draw = 0; $draw < self::PATTERN_ATTEMPTS / 2; $draw++) {
            $number = (float) $this->generateNumberExample($range, false);
            $forms = [(string) $number];

            for ($decimals = 2; $decimals >= 0; $decimals--) {
                $scale = 10 ** $decimals;
                $forms[] = number_format(floor($number * $scale) / $scale, $decimals, '.', '');
                $forms[] = number_format(ceil($number * $scale) / $scale, $decimals, '.', '');
            }

            foreach (array_unique($forms) as $form) {
                foreach ($this->paddedForms($form, $context->minLength ?? 0) as $padded) {
                    $first ??= $padded;

                    if (($context->maxLength === null || strlen($padded) <= $context->maxLength)
                        && $this->withinNumericBounds($context, (float) $padded)
                        && $this->isMultiple($context, (float) $padded)
                        && $context->matchesValueRules($padded)) {
                        return $padded;
                    }
                }
            }
        }

        return (string) $first;
    }

    /**
     * A decimal form padded to a minimum length: a whole one with leading
     * zeros (033) or, two characters short or more, a decimal point and zeros
     * (33.0); one with a point with trailing zeros. Never a bare trailing point.
     *
     * @return list<string>
     */
    private function paddedForms(string $form, int $minLength): array
    {
        $missing = $minLength - strlen($form);

        if ($missing <= 0) {
            return [$form];
        }

        if (str_contains($form, '.')) {
            return [$form . str_repeat('0', $missing)];
        }

        $sign = str_starts_with($form, '-') ? '-' : '';
        $padded = [$sign . str_repeat('0', $missing) . ltrim($form, '-')];

        return $missing >= 2 ? [...$padded, $form . '.' . str_repeat('0', $missing - 1)] : $padded;
    }

    private function isMultiple(ParameterContext $context, float $value): bool
    {
        foreach ([$context->multipleOf, $context->exampleMultipleOf] as $divisor) {
            if ($divisor !== null && abs(round($value / $divisor) * $divisor - $value) > 1e-9) {
                return false;
            }
        }

        return true;
    }

    private function withinNumericBounds(ParameterContext $context, float $value): bool
    {
        return ($context->minimum === null || $value >= $context->minimum)
            && ($context->maximum === null || $value <= $context->maximum)
            && ($context->exclusiveMinimum === null || $value > $context->exclusiveMinimum)
            && ($context->exclusiveMaximum === null || $value < $context->exclusiveMaximum);
    }

    /**
     * The strictest lower and upper bound over the four numeric bound fields, as
     * inclusive values. A float bound is moved onto the two-decimal grid the
     * example is drawn from: an exclusive bound to the nearest grid value inside
     * it, an inclusive one inwards.
     *
     * @return array{0: int|float|null, 1: int|float|null}
     */
    private function numericRange(ParameterContext $context, bool $integer): array
    {
        $lowers = [];
        $uppers = [];

        if ($context->minimum !== null) {
            $lowers[] = $integer ? $this->clampToInt(ceil($context->minimum)) : $context->minimum;
        }

        if ($context->exclusiveMinimum !== null) {
            $lowers[] = $integer ? $this->clampToInt($this->smallestIntegerAbove($context->exclusiveMinimum)) : (floor(round($context->exclusiveMinimum * 100, 6)) + 1) / 100;
        }

        if ($context->maximum !== null) {
            $uppers[] = $integer ? $this->clampToInt(floor($context->maximum)) : $context->maximum;
        }

        if ($context->exclusiveMaximum !== null) {
            $uppers[] = $integer ? $this->clampToInt($this->largestIntegerBelow($context->exclusiveMaximum)) : (ceil(round($context->exclusiveMaximum * 100, 6)) - 1) / 100;
        }

        $lower = $lowers === [] ? null : max($lowers);
        $upper = $uppers === [] ? null : min($uppers);

        if (! $integer) {
            $lower = $lower === null ? null : ceil(round($lower * 100, 6)) / 100;
            $upper = $upper === null ? null : floor(round($upper * 100, 6)) / 100;
        }

        return [$lower, $upper];
    }

    /**
     * A value inside the float bounds as published, for a range that holds no
     * two-decimal value ((0.001, 0.009)): the midpoint, or the one value an
     * inclusive range of width zero allows. Null when no value satisfies them.
     */
    private function offGridNumber(ParameterContext $context): ?float
    {
        $lower = max($context->minimum ?? -INF, $context->exclusiveMinimum ?? -INF);
        $upper = min($context->maximum ?? INF, $context->exclusiveMaximum ?? INF);
        $lowerExclusive = $context->exclusiveMinimum !== null && $context->exclusiveMinimum >= ($context->minimum ?? -INF);
        $upperExclusive = $context->exclusiveMaximum !== null && $context->exclusiveMaximum <= ($context->maximum ?? INF);

        if (! is_finite($lower) || ! is_finite($upper)) {
            return null;
        }

        if ($lower < $upper) {
            return (float) (($lower + $upper) / 2);
        }

        return $lower == $upper && ! $lowerExclusive && ! $upperExclusive ? (float) $lower : null;
    }

    /**
     * A published bound may lie beyond the int range (1e20); an integer example
     * cannot, so the bound is clamped before the cast.
     */
    private function clampToInt(float $value): int
    {
        return match (true) {
            $value >= PHP_INT_MAX => PHP_INT_MAX,
            $value <= PHP_INT_MIN => PHP_INT_MIN,
            default               => (int) $value,
        };
    }

    /**
     * The smallest integer strictly greater than $value: ceil(), or one more
     * than an already-whole $value. Ordinary float addition silently returns
     * $value again once its own spacing exceeds 1 (any integer beyond
     * 2^53), so that case steps to the next representable double instead,
     * which is itself always an integer at that magnitude.
     */
    private function smallestIntegerAbove(float $value): float
    {
        $ceiling = ceil($value);

        if ($ceiling > $value) {
            return $ceiling;
        }

        $incremented = $ceiling + 1;

        return $incremented > $ceiling ? $incremented : $this->nextRepresentableFloat($ceiling, true);
    }

    /**
     * The largest integer strictly less than $value, symmetric to
     * smallestIntegerAbove.
     */
    private function largestIntegerBelow(float $value): float
    {
        $floor = floor($value);

        if ($floor < $value) {
            return $floor;
        }

        $decremented = $floor - 1;

        return $decremented < $floor ? $decremented : $this->nextRepresentableFloat($floor, false);
    }

    /**
     * The next IEEE-754 double after a finite $value, found by stepping its
     * bit pattern by one ULP. Used only once ordinary +1/-1 has already been
     * shown to round straight back to $value at its own magnitude.
     */
    private function nextRepresentableFloat(float $value, bool $up): float
    {
        if (! is_finite($value)) {
            return $value;
        }

        $bits = unpack('Q', pack('d', $value))[1];
        $bits += ($value >= 0) === $up ? 1 : -1;

        return unpack('d', pack('Q', $bits))[1];
    }

    /**
     * a/b rounded toward positive infinity, using intdiv/% so it stays exact
     * at magnitudes where float division loses precision: dividing two ints
     * that do not divide evenly always yields a float, which cannot hold
     * every integer once either operand nears PHP_INT_MAX.
     */
    private function ceilDiv(int $numerator, int $divisor): int
    {
        $quotient = intdiv($numerator, $divisor);
        $remainder = $numerator % $divisor;

        return $remainder !== 0 && ($remainder > 0) === ($divisor > 0) ? $quotient + 1 : $quotient;
    }

    /**
     * a/b rounded toward negative infinity, symmetric to ceilDiv.
     */
    private function floorDiv(int $numerator, int $divisor): int
    {
        $quotient = intdiv($numerator, $divisor);
        $remainder = $numerator % $divisor;

        return $remainder !== 0 && ($remainder > 0) !== ($divisor > 0) ? $quotient - 1 : $quotient;
    }

    private function generateNumberExample(ParameterContext $context, bool $integer): int|float
    {
        [$min, $max] = $this->numericRange($context, $integer);
        $hasUpper = $max !== null;

        // With only an upper bound the range starts at 1, or at it when it is below 1; a divisor needs room for a multiple, so its range runs from 0 or down from the bound, and ten divisors wide at least.
        $divided = $context->multipleOf !== null || $context->exampleMultipleOf !== null;
        $largest = max(abs($context->multipleOf ?? 0), abs($context->exampleMultipleOf ?? 0));
        $room = $divided ? max(100, 10 * $largest) : 100;
        $defaultMin = match (true) {
            $max === null => 1,
            $divided      => $max > 0 ? 0 : $max - $room,
            $max >= 1     => 1,
            default       => $max,
        };

        if ($integer) {
            $min = (int) ($min ?? $defaultMin);
            $max = $max !== null ? (int) $max : ($divided ? $this->clampToInt($min + $room) : 100);
        } else {
            $min = (float) ($min ?? $defaultMin);
            $max = (float) ($max ?? ($divided ? $min + $room : 100.0));
        }

        if ($min > $max && ! $integer && $context->multipleOf === null && ($offGrid = $this->offGridNumber($context)) !== null) {
            return $offGrid;
        }

        if ($min > $max) {
            $max = $integer ? $this->clampToInt($min + $room) : $min + $room;
        }

        // Both divisors apply (#[Digits(3), MultipleOf(1.5)] on a float): a multiple of the other that the published one divides.
        if ($context->multipleOf !== null && $context->exampleMultipleOf !== null
            && ($multiple = $this->multipleBetween($min, $max, $context->exampleMultipleOf, true, $context->multipleOf)) !== null) {
            return $multiple;
        }

        // A whole-number example is an int even on a number field: a float of
        // fifteen digits or more is written in exponent form, which digits rejects.
        if ($context->multipleOf !== null) {
            // On an integer field, exact int division keeps a bound near PHP_INT_MAX from losing precision to float division.
            $rangeStart = is_int($min) ? $this->ceilDiv($min, $context->multipleOf) : $this->clampToInt(ceil($min / $context->multipleOf));
            $rangeEnd = is_int($max) ? $this->floorDiv($max, $context->multipleOf) : $this->clampToInt(floor($max / $context->multipleOf));
            // Declared bounds with no multiple between them: the first multiple above the lower one, not numberBetween's swapped range.
            $multiplier = $rangeStart > $rangeEnd ? $rangeStart : $this->faker->numberBetween($rangeStart, $rangeEnd);

            // Beyond the int range the product would be a float, which an int cast wraps.
            if (abs($multiplier) > intdiv(PHP_INT_MAX, $context->multipleOf)) {
                return $integer ? ($multiplier > 0 ? PHP_INT_MAX : PHP_INT_MIN) : (float) $multiplier * $context->multipleOf;
            }

            return $multiplier * $context->multipleOf;
        }

        if ($context->exampleMultipleOf !== null && ($multiple = $this->multipleBetween($min, $max, $context->exampleMultipleOf, $integer)) !== null) {
            return $multiple;
        }

        if ($integer) {
            return $this->faker->numberBetween($min, $max);
        }

        if (! $hasUpper) {
            $defaultMax = $min + 100.0;

            return $this->faker->randomFloat(2, $min, $defaultMax);
        }

        return $this->faker->randomFloat(2, $min, $max);
    }

    /**
     * A multiple of a divisor multipleOf cannot publish (#[MultipleOf(0.1)]),
     * rounded to the divisor's decimals so Laravel's exact decimal check
     * passes. On an integer field the step is the smallest whole multiple of
     * the divisor, and of $wholeMultiple when a published multipleOf also
     * applies. Null when the range holds none.
     */
    private function multipleBetween(int|float $min, int|float $max, int|float $divisor, bool $integer, int $wholeMultiple = 1): int|float|null
    {
        $text = rtrim(rtrim(number_format($divisor, 10, '.', ''), '0'), '.');
        $decimals = ($point = strpos($text, '.')) === false ? 0 : strlen($text) - $point - 1;
        $step = $divisor;

        if ($integer) {
            $factor = 1;

            while ($factor <= 1000 && (floor(round($divisor * $factor, $decimals)) != round($divisor * $factor, $decimals)
                || fmod(round($divisor * $factor, $decimals), $wholeMultiple) != 0)) {
                $factor++;
            }

            if ($factor > 1000) {
                return null;
            }

            $step = round($divisor * $factor, $decimals);
            $decimals = 0;
        }

        $first = ceil(round($min / $step, 6));
        $last = floor(round($max / $step, 6));

        if ($first > $last) {
            return null;
        }

        $value = round($this->faker->numberBetween($this->clampToInt($first), $this->clampToInt($last)) * $step, $decimals);

        return $decimals === 0 ? $this->clampToInt($value) : $value;
    }

    /**
     * An item of an array custom type, drawn as a field of the item type with
     * the constraints published under items. The array's own attributes apply
     * to the array itself, so only the custom type's pattern decides.
     */
    private function itemExample(ParameterContext $context, string $baseType): mixed
    {
        $item = clone $context;
        $item->type = $baseType;
        $item->example = null;
        $item->minItems = $item->maxItems = null;
        $item->pattern = $item->format = null;
        $item->minLength = $item->maxLength = $item->multipleOf = null;
        $item->minimum = $item->maximum = $item->exclusiveMinimum = $item->exclusiveMaximum = null;
        $item->allowedValues = $item->excludedValues = $item->enumInfo = null;
        $item->valueRegexes = $item->valueRules = $item->regexPatterns = [];
        $item->dateFormat = $item->exampleFormat = $item->uriScheme = $item->exampleMultipleOf = null;
        $item->numericString = false;

        foreach ($context->itemSchema as $field => $value) {
            $item->{$field} = $value;
        }

        $pattern = $context->itemSchema['pattern'] ?? null;
        $item->valuePatterns = is_string($pattern) ? [$pattern] : [];
        $item->itemSchema = [];

        return $this->generate($item)->example;
    }

    /**
     * As many items as the array's bounds ask for: its minimum, at least one,
     * within its maximum.
     */
    private function itemCount(ParameterContext $context): int
    {
        return max(min(max($context->minItems ?? 1, 1), $context->maxItems ?? PHP_INT_MAX), 0);
    }

    /**
     * Distinct values where the list has enough, else repeated ones: in, like
     * an enum rule, checks each item alone.
     *
     * @param  list<mixed>  $values
     * @return list<mixed>
     */
    private function itemsFrom(array $values, int $count): array
    {
        return $count <= count($values)
            ? $this->faker->randomElements($values, $count)
            : array_map(fn() => $this->faker->randomElement($values), array_fill(0, $count, null));
    }

    private function generateArrayExample(ParameterContext $context): array
    {
        $baseType = str_replace('[]', '', $context->type ?? '');

        $itemCount = $this->itemCount($context);

        if ($context->itemSchema !== []) {
            return array_map(fn() => $this->itemExample($context, $baseType), array_fill(0, $itemCount, null));
        }

        $items = [];
        for ($i = 0; $i < $itemCount; $i++) {
            $items[] = match ($baseType) {
                'string'  => $this->faker->word(),
                'integer' => $this->faker->numberBetween(1, 100),
                'number'  => $this->faker->randomFloat(2, 1, 100),
                'boolean' => $this->faker->boolean(),
                default   => null,
            };
        }

        return $items;
    }
}
