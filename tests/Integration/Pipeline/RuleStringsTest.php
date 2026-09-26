<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\ExcludeIf as ExcludeIfRule;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exclude;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function ruleStringThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(RuleStringsTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

/**
 * The published parameter without its name and its generated example, which
 * differ between two properties by construction.
 */
function comparableParameter(string $property): array
{
    $published = ruleStringThroughPipeline($property)->toParameter()->toArray();
    unset($published['name'], $published['example']);

    return $published;
}

it('documents a rule string exactly as the attribute it expands to', function (string $attribute, string $ruleString) {
    expect(comparableParameter($ruleString))->toBe(comparableParameter($attribute));
})->with([
    'size and bounds'         => ['attribute_min', 'rule_min'],
    'comparison'              => ['attribute_gt', 'rule_gt'],
    'text patterns'           => ['attribute_regex', 'rule_regex'],
    'dates and times'         => ['attribute_date_format', 'rule_date_format'],
    'arrays and assertions'   => ['attribute_digits', 'rule_digits'],
    'identifiers, email'      => ['attribute_email', 'rule_email'],
    'identifiers, url'        => ['attribute_url', 'rule_url'],
    'cross-field'             => ['attribute_same', 'rule_same'],
    'acceptance'              => ['attribute_accepted', 'rule_accepted'],
    'conditional requirement' => ['attribute_required_if', 'rule_required_if'],
    'prohibition'             => ['attribute_prohibited_if', 'rule_prohibited_if'],
    'exclusion, bare keyword' => ['attribute_exclude', 'rule_exclude'],
]);

it('keeps a bare exclude rule string unconditional when a conditional Exclude follows', function () {
    // Upstream the rule string stays a plain Rule, which the later Exclude
    // does not drop, so the field is excluded in every request.
    $rules = RuleStringsTestData::getValidationRules([])['exclude_then_conditional'];
    $validated = Validator::make(['exclude_then_conditional' => 'v'], ['exclude_then_conditional' => $rules])->validated();

    expect($validated)->not->toHaveKey('exclude_then_conditional')
        ->and(ruleStringThroughPipeline('exclude_then_conditional')->description)
        ->toContain('Not validated, and removed from the validated input.');
});

it('documents a bare exclude rule string once after an Exclude attribute', function () {
    expect(substr_count(ruleStringThroughPipeline('exclude_then_rule')->description, 'Not validated, and removed from the validated input.'))->toBe(1);
});

it('reads a conditional rule string at its own position for suppression', function () {
    expect(ruleStringThroughPipeline('rule_required_if_then_present')->description)->not->toContain('Required when')
        ->and(ruleStringThroughPipeline('present_then_rule_required_if')->description)->toContain('Required when <b><i>a</i></b> is <code>x</code>.');
});

it('documents nothing for a rule string a later attribute replaces', function () {
    $context = ruleStringThroughPipeline('rule_min_then_attribute');

    expect($context->minLength)->toBe(5)
        ->and($context->description)->not->toContain('minimum <code>3</code>');
});

it('documents every rule a single rule string expands to', function () {
    $context = ruleStringThroughPipeline('rule_min_and_max');

    expect($context->minLength)->toBe(3)
        ->and($context->maxLength)->toBe(9);
});

it('records the confirmation companion for a confirmed rule string', function () {
    expect(ruleStringThroughPipeline('password')->confirmationCompanion?->name)->toBe('password_confirmation');
});

it('ignores a rule string with an unknown keyword', function () {
    $context = ruleStringThroughPipeline('rule_unknown');

    expect($context->descriptions)->toBe([])
        ->and($context->toParameter()->openApiAttributes)->toBe([]);
});

class RuleStringsTestData extends Data
{
    public function __construct(
        #[Min(3)]
        public string $attribute_min,
        #[Rule('min:3')]
        public string $rule_min,
        #[GreaterThan(5)]
        public int $attribute_gt,
        #[Rule('gt:5')]
        public int $rule_gt,
        #[Regex('/^[a-z]+$/')]
        public string $attribute_regex,
        #[Rule('regex:/^[a-z]+$/')]
        public string $rule_regex,
        #[DateFormat('Y-m-d')]
        public string $attribute_date_format,
        #[Rule('date_format:Y-m-d')]
        public string $rule_date_format,
        #[Digits(4)]
        public string $attribute_digits,
        #[Rule('digits:4')]
        public string $rule_digits,
        #[Email('rfc', 'dns')]
        public string $attribute_email,
        #[Rule('email:rfc,dns')]
        public string $rule_email,
        #[Url('https')]
        public string $attribute_url,
        #[Rule('url:https')]
        public string $rule_url,
        #[Same('a')]
        public string $attribute_same,
        #[Rule('same:a')]
        public string $rule_same,
        #[Accepted]
        public bool $attribute_accepted,
        #[Rule('accepted')]
        public bool $rule_accepted,
        #[RequiredIf('a', 'x')]
        public ?string $attribute_required_if,
        #[Rule('required_if:a,x')]
        public ?string $rule_required_if,
        #[ProhibitedIf('a', 'x')]
        public ?string $attribute_prohibited_if,
        #[Rule('prohibited_if:a,x')]
        public ?string $rule_prohibited_if,
        #[Exclude]
        public ?string $attribute_exclude,
        #[Rule('exclude')]
        public ?string $rule_exclude,
        #[Exclude, Rule('exclude')]
        public ?string $exclude_then_rule,
        #[Rule('exclude'), Exclude(new ExcludeIfRule(false))]
        public ?string $exclude_then_conditional,
        #[Rule('required_if:a,x'), Present]
        public ?string $rule_required_if_then_present,
        #[Present, Rule('required_if:a,x')]
        public ?string $present_then_rule_required_if,
        #[Rule('min:3'), Min(5)]
        public string $rule_min_then_attribute,
        #[Rule('min:3|max:9')]
        public string $rule_min_and_max,
        #[Rule('confirmed')]
        public string $password,
        #[Rule('unknown_rule')]
        public string $rule_unknown,
        public string $a = '',
    ) {}
}
