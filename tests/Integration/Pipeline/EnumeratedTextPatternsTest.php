<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\Alpha;
use Spatie\LaravelData\Attributes\Validation\AlphaNumeric;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\Lowercase;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\Uppercase;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\EndsWith;
use Spatie\LaravelData\Attributes\Validation\IP;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function textPatternThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(EnumeratedTextPatternsTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function textPatternPasses(string $property, string $value): bool
{
    $rules = EnumeratedTextPatternsTestData::getValidationRules([])[$property];

    return Validator::make([$property => $value], [$property => $rules])->passes();
}

it('publishes a declared regex as the ECMA-262 pattern Laravel applies', function (string $property, string $pattern, array $accepted, array $rejected) {
    $context = textPatternThroughPipeline($property);

    expect($context->pattern)->toBe($pattern)
        ->and($context->toParameter()->openApiAttributes['pattern'])->toBe($pattern);

    foreach ($accepted as $value) {
        expect(textPatternPasses($property, $value))->toBeTrue("Laravel should accept {$value}")
            ->and(preg_match("\x01{$pattern}\x01u", $value))->toBe(1, "{$pattern} should accept {$value}");
    }

    foreach ($rejected as $value) {
        expect(textPatternPasses($property, $value))->toBeFalse("Laravel should reject {$value}")
            ->and(preg_match("\x01{$pattern}\x01u", $value))->toBe(0, "{$pattern} should reject {$value}");
    }
})->with([
    'slash delimiters'       => ['slash', '^[a-z]+$', ['abc'], ['ABC', '/abc/', 'ab1']],
    'hash delimiters'        => ['hash', '^a/b$', ['a/b'], ['ab', '#a/b#']],
    'tilde delimiters'       => ['tilde', '^\d{3}-\d{4}$', ['555-1234'], ['5551234']],
    'brace delimiters'       => ['brace', '^a{2}$', ['aa'], ['a', 'aaa', '{aa}']],
    'parenthesis delimiters' => ['paren', '^a(b)$', ['ab'], ['a', '(ab)']],
    'angle delimiters'       => ['angle', '^x+$', ['xxx'], ['<x>']],
    'square delimiters'      => ['square', '^a', ['ab'], ['ba']],
    'leading whitespace, D'  => ['spaced', '^ab$', ['ab'], ['ab ', 'xab']],
    'escaped delimiter'      => ['escapedHash', '^a#b$', ['a#b'], ['ab']],
    'escaped slash'          => ['url', '^https?:\/\/\S+$', ['https://example.com'], ['ftp://example.com']],
    'anchored modifier'      => ['anchored', '^(?:b|c)', ['bx', 'c'], ['ab']],
    'unicode modifier'       => ['unicode', '^\p{Lu}[0-9]{2}$', ['A12', 'É12'], ['a12']],
    'ungreedy modifier'      => ['ungreedy', '^a+?b$', ['aab'], ['b']],
    'no-capture modifier'    => ['noCapture', '^(a)(?<x>b)$', ['ab'], ['abb']],
    'leading ] in a class'   => ['classBracket', '^[\]a]+$', [']a'], ['b']],
    'escaped - in a class'   => ['classDash', '^[\w.\-]+$', ['a.b-c'], ['a b']],
    'literal braces'         => ['literalBrace', '^a\{x\}$', ['a{x}'], ['ax']],
]);

it('publishes no pattern for a regex ECMA-262 cannot read the same way, and keeps its sentence', function (string $property, string $regex) {
    $context = textPatternThroughPipeline($property);

    expect($context->pattern)->toBeNull()
        ->and($context->toParameter()->openApiAttributes)->not->toHaveKey('pattern')
        ->and($context->description)->toContain("Must match the regex <code>{$regex}</code>.");
})->with([
    'case-insensitive'           => ['caseInsensitive', '/^[a-z]+$/i'],
    'multiline'                  => ['multiline', '/^a$/m'],
    'dot-all'                    => ['dotAll', '/^a.b$/s'],
    'extended'                   => ['extended', '/^a b$/x'],
    'duplicate names'            => ['duplicateNames', '/(?<n>a)|(?<n>b)/J'],
    'no-capture with backref'    => ['noCaptureBackreference', '/^(a)(?<x>b)\1$/n'],
    'backreference'              => ['backreference', '/^(ab)\1$/'],
    'optional group reference'   => ['optionalReference', '/^(?:(a)|b)\1$/'],
    'quantified lookahead'       => ['quantifiedLookahead', '/^(?=a)?a/'],
    'subject anchors'            => ['subjectAnchors', '/\Aabc\z/'],
    'horizontal space'           => ['horizontalSpace', '/^a\hb$/'],
    'inline flag'                => ['inlineFlag', '/^(?i)abc$/'],
    'atomic group'               => ['atomicGroup', '/^(?>a+)b$/'],
    'possessive quantifier'      => ['possessive', '/^a++b$/'],
    'quantifier with no minimum' => ['noMinimum', '/^a{,3}$/'],
    'POSIX class'                => ['posixClass', '/^[[:alpha:]]+$/'],
    // With u, PCRE reads \w and \d as Unicode classes; ECMA-262 keeps them ASCII.
    'unicode word class'  => ['unicodeWord', '/^\w+$/u'],
    'unicode digit class' => ['unicodeDigit', '/^\d{3}$/u'],
    'braced hex escape'   => ['bracedHex', '/^\x{41}$/u'],
    'script property'     => ['script', '/^\p{Greek}$/u'],
    'verb'                => ['verb', '/(*UTF8)^a$/'],
    'no delimiter'        => ['noDelimiter', 'abc'],
    'unknown modifier'    => ['unknownModifier', '/a/b/'],
]);

it('generates a string example that meets both a format and a pattern', function (string $property) {
    foreach (range(1, 25) as $run) {
        $example = textPatternThroughPipeline($property)->example;

        expect(textPatternPasses($property, $example))->toBeTrue("{$example} should pass");
    }
})->with(['emailStartingWithA', 'emailEndingWithCom', 'uuidStartingWithA', 'urlEndingWithPdf', 'urlEndingWithCom', 'ipStartingWith10', 'emailStartingAndEnding', 'uuidStartingAndEnding']);

it('generates a pattern example when no format example can match the pattern', function () {
    $context = textPatternThroughPipeline('alphabeticEmail');

    expect(preg_match("\x01{$context->pattern}\x01u", $context->example))->toBe(1);
});

it('publishes the prefixes and suffixes Laravel applies, split at commas', function (string $property, string $pattern, array $accepted, array $rejected) {
    $published = textPatternThroughPipeline($property)->pattern;

    expect($published)->toBe($pattern);

    foreach ($accepted as $value) {
        expect(textPatternPasses($property, $value))->toBeTrue("{$value} should pass")
            ->and(preg_match("/{$published}/u", $value))->toBe(1);
    }

    foreach ($rejected as $value) {
        expect(textPatternPasses($property, $value))->toBeFalse("{$value} should fail")
            ->and(preg_match("/{$published}/u", $value))->toBe(0);
    }
})->with([
    'comma-joined prefixes' => ['prefixList', '^(a|b)', ['abc', 'bcd'], ['cde']],
    'comma-joined suffixes' => ['suffixList', '(x|y)$', ['ax', 'by'], ['az']],
]);

it('states that a prefix or suffix rule with no value fails every request', function (string $property) {
    $context = textPatternThroughPipeline($property);

    expect($context->pattern)->toBeNull()
        ->and($context->description)->toContain('but none is given, so any request that sends this field fails validation.');

    foreach (['a', 'abc', ''] as $value) {
        expect(textPatternPasses($property, $value))->toBeFalse();
    }
})->with(['emptyPrefix', 'emptySuffix']);

it('generates a pattern example that meets the length bounds and every pattern rule', function (string $property) {
    foreach (range(1, 25) as $run) {
        $example = textPatternThroughPipeline($property)->example;

        expect(textPatternPasses($property, $example))->toBeTrue("{$property}: {$example} should pass");
    }
})->with(['upperMin', 'alnumSize', 'prefixMin', 'suffixMin', 'alphaMin', 'alphaMax', 'prefixDigits', 'lowerDigits', 'prefixAndSuffix', 'prefixAndSuffixMin', 'spacedRegex', 'prefixAndRegex', 'caseInsensitiveCode', 'subjectAnchorDigits', 'caseInsensitiveMin', 'unicodeUpperDigits', 'unicodeLetters', 'namedGroup', 'negatedClass', 'optionalLetters', 'jsonArray', 'lowercaseEmail', 'lowercaseUrl', 'uppercaseUuid', 'urlRegexPdf', 'emailRegexA', 'uuidRegexA', 'uppercaseUlid']);

it('gives a padded pattern example no edge space, which Laravel\'s TrimStrings would strip', function () {
    foreach (range(1, 40) as $run) {
        $example = textPatternThroughPipeline('spacedMin')->example;

        expect(trim($example))->toBe($example)
            ->and(textPatternPasses('spacedMin', $example))->toBeTrue();
    }
});

it('judges an approximate pattern beside a Regex by the example too, and keeps a regex pattern', function () {
    // lowercase accepts 333, which ^[a-z]+$ rejects; a published regex translation is exact.
    expect(textPatternThroughPipeline('regexDigitsLower')->toParameter()->openApiAttributes)->not->toHaveKey('pattern')
        ->and(textPatternThroughPipeline('lowerThenRegex')->toParameter()->openApiAttributes['pattern'] ?? null)->toBe('^[a-z]{3}$');
});

it('leaves out an approximate pattern the field\'s example fails, which Laravel\'s rules accept', function () {
    // Digits(3) makes the example 007, which lowercase accepts and ^[a-z]+$ rejects.
    $contradicted = textPatternThroughPipeline('lowerDigits')->toParameter()->openApiAttributes;
    $alone = textPatternThroughPipeline('lowercaseAlone')->toParameter()->openApiAttributes;

    expect($contradicted)->not->toHaveKey('pattern')
        ->and($alone['pattern'] ?? null)->toBe('^[a-z]+$');
});

class EnumeratedTextPatternsTestData extends Data
{
    public function __construct(
        #[Min(15), Uppercase]
        public string $upperMin,
        #[StartsWith('img_'), EndsWith('.png')]
        public string $prefixAndSuffix,
        #[StartsWith('ab'), EndsWith('yz'), Min(10)]
        public string $prefixAndSuffixMin,
        #[Regex('/^[A-Z][a-z]+\s[A-Z][a-z]+$/')]
        public string $spacedRegex,
        #[StartsWith('ab'), Regex('/^[a-z]{4}$/')]
        public string $prefixAndRegex,
        // Regexes that publish no pattern: the declared regex is drawn from and decides.
        #[Regex('/^[A-Z]{2}\d{4}$/i')]
        public string $caseInsensitiveCode,
        #[Regex('/^\d{5}\z/')]
        public string $subjectAnchorDigits,
        #[Regex('/^[a-z]+$/i'), Min(20)]
        public string $caseInsensitiveMin,
        // Patterns Faker cannot expand as written.
        #[Regex('/^\p{Lu}[0-9]{2}$/u')]
        public string $unicodeUpperDigits,
        #[Regex('/^\p{L}+$/u')]
        public string $unicodeLetters,
        #[Regex('/^(?<code>[a-z]{3})-[0-9]{2}$/')]
        public string $namedGroup,
        #[Regex('/^[^0-9 ]+$/')]
        public string $negatedClass,
        // An empty draw passes Laravel's non-required rules but is no example.
        #[Regex('/^[a-z]*$/')]
        public string $optionalLetters,
        #[Json, StartsWith('[')]
        public string $jsonArray,
        // A format beside an approximate pattern: Laravel's own rules decide.
        #[Email, Lowercase]
        public string $lowercaseEmail,
        #[Url, Lowercase]
        public string $lowercaseUrl,
        #[Uuid, Uppercase]
        public string $uppercaseUuid,
        // A format beside a regex that publishes no pattern.
        #[Url, Regex('/\.pdf$/i')]
        public string $urlRegexPdf,
        #[Email, Regex('/^a/i')]
        public string $emailRegexA,
        #[Uuid, Regex('/^a/i')]
        public string $uuidRegexA,
        #[Ulid, Uppercase]
        public string $uppercaseUlid,
        #[Regex('/^\d{3}$/'), Lowercase]
        public string $regexDigitsLower,
        #[Lowercase, Regex('/^[a-z]{3}$/')]
        public string $lowerThenRegex,
        #[Url, EndsWith('.pdf')]
        public string $urlEndingWithPdf,
        #[Url, EndsWith('.com')]
        public string $urlEndingWithCom,
        #[IP, StartsWith('10.')]
        public string $ipStartingWith10,
        #[Email, StartsWith('a'), EndsWith('.com')]
        public string $emailStartingAndEnding,
        #[Uuid, StartsWith('a'), EndsWith('f')]
        public string $uuidStartingAndEnding,
        #[Size(12), AlphaNumeric]
        public string $alnumSize,
        #[Min(20), StartsWith('abc')]
        public string $prefixMin,
        #[Min(12), EndsWith('.pdf')]
        public string $suffixMin,
        #[Min(20), Alpha]
        public string $alphaMin,
        #[Max(3), Alpha]
        public string $alphaMax,
        #[StartsWith('09'), Digits(11)]
        public string $prefixDigits,
        #[Lowercase, Digits(3)]
        public string $lowerDigits,
        #[Lowercase]
        public string $lowercaseAlone,
        #[Regex('/^[a-z ]+$/'), Min(20)]
        public string $spacedMin,
        #[StartsWith('a,b')]
        public string $prefixList,
        #[EndsWith('x', 'y,')]
        public string $suffixList,
        #[StartsWith('')]
        public string $emptyPrefix,
        #[EndsWith('', '')]
        public string $emptySuffix,
        #[Regex('/^[a-z]+$/')]
        public string $slash,
        #[Regex('#^a/b$#')]
        public string $hash,
        #[Regex('~^\d{3}-\d{4}$~')]
        public string $tilde,
        #[Regex('{^a{2}$}')]
        public string $brace,
        #[Regex('(^a(b)$)')]
        public string $paren,
        #[Regex('<^x+$>')]
        public string $angle,
        #[Regex('[^a]')]
        public string $square,
        #[Regex("  /^ab$/D \n")]
        public string $spaced,
        #[Regex('#^a\#b$#')]
        public string $escapedHash,
        #[Regex('/^https?:\/\/\S+$/')]
        public string $url,
        #[Regex('/b|c/A')]
        public string $anchored,
        #[Regex('/^\p{Lu}[0-9]{2}$/u')]
        public string $unicode,
        #[Regex('/^\w+$/u')]
        public string $unicodeWord,
        #[Regex('/^\d{3}$/u')]
        public string $unicodeDigit,
        #[Regex('/^a+?b$/U')]
        public string $ungreedy,
        #[Regex('/^(a)(?<x>b)$/n')]
        public string $noCapture,
        #[Regex('/^(ab)\1$/')]
        public string $backreference,
        #[Regex('/^(?:(a)|b)\1$/')]
        public string $optionalReference,
        #[Regex('/^(?=a)?a/')]
        public string $quantifiedLookahead,
        #[Regex('/^[]a]+$/')]
        public string $classBracket,
        #[Regex('/^[\w.\-]+$/')]
        public string $classDash,
        #[Regex('/^a{x}$/')]
        public string $literalBrace,
        #[Regex('/^[a-z]+$/i')]
        public string $caseInsensitive,
        #[Regex('/^a$/m')]
        public string $multiline,
        #[Regex('/^a.b$/s')]
        public string $dotAll,
        #[Regex('/^a b$/x')]
        public string $extended,
        #[Regex('/(?<n>a)|(?<n>b)/J')]
        public string $duplicateNames,
        #[Regex('/^(a)(?<x>b)\1$/n')]
        public string $noCaptureBackreference,
        #[Regex('/\Aabc\z/')]
        public string $subjectAnchors,
        #[Regex('/^a\hb$/')]
        public string $horizontalSpace,
        #[Regex('/^(?i)abc$/')]
        public string $inlineFlag,
        #[Regex('/^(?>a+)b$/')]
        public string $atomicGroup,
        #[Regex('/^a++b$/')]
        public string $possessive,
        #[Regex('/^a{,3}$/')]
        public string $noMinimum,
        #[Regex('/^[[:alpha:]]+$/')]
        public string $posixClass,
        #[Regex('/^\x{41}$/u')]
        public string $bracedHex,
        #[Regex('/^\p{Greek}$/u')]
        public string $script,
        #[Regex('/(*UTF8)^a$/')]
        public string $verb,
        #[Regex('abc')]
        public string $noDelimiter,
        #[Regex('/a/b/')]
        public string $unknownModifier,
        #[Email, StartsWith('a')]
        public string $emailStartingWithA,
        #[Email, EndsWith('.com')]
        public string $emailEndingWithCom,
        #[Uuid, StartsWith('a')]
        public string $uuidStartingWithA,
        #[Email, Alpha]
        public string $alphabeticEmail,
    ) {}
}
