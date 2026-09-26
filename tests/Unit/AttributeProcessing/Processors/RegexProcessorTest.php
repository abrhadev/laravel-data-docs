<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RegexProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

beforeEach(function () {
    $this->processor = new RegexProcessor();
});

it('sets pattern and appends description', function () {
    $testData = new class ('test') extends Data {
        public function __construct(
            #[Regex('/^[a-z]+$/')]
            public string $pattern,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'pattern');

    $context = new ParameterContext('pattern', $property);
    $context->type = 'string';

    $attribute = new Regex('/^[a-z]+$/');
    $this->processor->process($attribute, $context);

    expect($context->pattern)->toBe('^[a-z]+$')
        ->and($context->descriptions)->toContain('Must match the regex <code>/^[a-z]+$/</code>.');
});

it('translates the body of a declared regex to ECMA-262', function (string $regex, string $pattern) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Regex($regex), $context);

    expect($context->pattern)->toBe($pattern);
})->with([
    'bracket delimiters nest'           => ['{^(a{2}){1,}$}', '^(a{2}){1,}$'],
    'escaped non-syntax character'      => ['/^a\:b\ c$/', '^a:b c$'],
    'escaped syntax character'          => ['/^\$\.\*$/', '^\$\.\*$'],
    'lone closing brackets'             => ['/^a}]$/', '^a\}\]$'],
    'negated class with leading ]'      => ['/^[^]a]$/', '^[^\]a]$'],
    'opening bracket in a class'        => ['/^[[a]$/', '^[\[a]$'],
    'kept escapes'                      => ['/^(?<n>\x41)\cJ\0\t$/', '^(?<n>\x41)\cJ\0\t$'],
    'lookarounds and named groups'      => ['/^(?=a)(?!b)(?<=c)(?<!d)(?<n>e)(?:f)+$/', '^(?=a)(?!b)(?<=c)(?<!d)(?<n>e)(?:f)+$'],
    'lookaround with a quantified body' => ['/^(?=a+)(?!(b)?)x$/', '^(?=a+)(?!(b)?)x$'],
    'lazy quantifiers'                  => ['/^a*?b+?c??d{1,2}?$/', '^a*?b+?c??d{1,2}?$'],
    'dollar-end-only and study'         => ['/^a$/DS', '^a$'],
]);

it('publishes no pattern for a PCRE-only escape or group', function (string $regex) {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Regex($regex), $context);

    expect($context->pattern)->toBeNull()
        ->and($context->descriptions)->toBe(["Must match the regex <code>{$regex}</code>."]);
})->with([
    'vertical space class'     => ['/^\v$/'],
    'octal escape'             => ['/^\012$/'],
    'two-digit reference'      => ['/^(a)\10$/'],
    'digit in a class'         => ['/^[\1]$/'],
    'single-letter property'   => ['/^\pL$/u'],
    'named group PCRE way'     => ['/^(?P<n>a)$/'],
    'quantifier with space'    => ['/^a{ 2 }$/'],
    'numbered backreference'   => ['/^(ab)\1$/'],
    'optional group reference' => ['/^(?:(a)|b)\1$/'],
    'named backreference'      => ['/^(?<n>a)\k<n>$/'],
    'quantified lookahead'     => ['/^(?=a)?a/'],
    'quantified lookbehind'    => ['/^a(?<=a)*$/'],
    'counted lookahead'        => ['/^(?!b){2}a$/'],
]);

it('keeps an earlier pattern when the regex cannot be translated', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->pattern = '^(a)';

    $this->processor->process(new Regex('/^a/i'), $context);

    expect($context->pattern)->toBe('^(a)')
        ->and($context->descriptions)->toBe(['Must match the regex <code>/^a/i</code>.']);
});

it('skips an external reference it cannot document', function () {
    $context = conditionContext();
    $context->type = 'string';

    $this->processor->process(new Regex(new RouteParameterReference('limit')), $context);

    expect($context->descriptions)->toBe([])
        ->and($context->pattern)->toBeNull();
});
