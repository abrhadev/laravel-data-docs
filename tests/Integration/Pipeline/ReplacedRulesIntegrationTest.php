<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Filled;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Prohibited;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function replacedThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ReplacedRulesTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function enforcedRules(string $property): array
{
    return array_map('strval', ReplacedRulesTestData::getValidationRules([])[$property]);
}

it('documents the replacing rule instead of an attribute a later same-class Rule replaces', function (string $property, string $enforced, string $dropped, string $droppedSentence, string $keptSentence, array $fields) {
    $context = replacedThroughPipeline($property);
    $published = $context->toParameter()->openApiAttributes;

    expect(enforcedRules($property))->toContain($enforced)->not->toContain($dropped)
        ->and($context->description)->not->toContain($droppedSentence)
        ->and($context->description)->toContain($keptSentence);

    foreach ($fields as $field => $value) {
        expect($published[$field] ?? null)->toBe($value);
    }
})->with([
    'size and bounds'         => ['min_replaced', 'min:3', 'min:5', 'minimum <code>5</code>', 'minimum <code>3</code>', ['minLength' => 3]],
    'comparison'              => ['gt_replaced', 'gt:3', 'gt:5', 'greater than <code>5</code>', 'greater than <code>3</code>', ['exclusiveMinimum' => 3]],
    'text patterns'           => ['regex_replaced', 'regex:/^b/', 'regex:/^a/', '<code>/^a/</code>', '<code>/^b/</code>', ['pattern' => '^b']],
    'dates and times'         => ['date_format_replaced', 'date_format:d/m/Y', 'date_format:Y-m-d', '<code>Y-m-d</code>', '<code>d/m/Y</code>', ['format' => null]],
    'arrays and assertions'   => ['digits_replaced', 'digits:4', 'digits:3', '<code>3</code> digits', '<code>4</code> digits', ['pattern' => '^[0-9]{4}$', 'minLength' => 4, 'maxLength' => 4]],
    'identifiers'             => ['email_replaced', 'email:dns', 'email:rfc', 'Must be a valid email address.', 'DNS', ['format' => 'email']],
    'cross-field'             => ['same_replaced', 'same:b', 'same:a', '<b><i>a</i></b>', '<b><i>b</i></b>', []],
    'conditional requirement' => ['required_if_replaced', 'required_if:b,y', 'required_if:a,x', '<b><i>a</i></b>', '<b><i>b</i></b>', []],
]);

it('publishes the bound that a later equal rule, another class or an earlier Rule leaves in force', function (string $property, string $enforced, string $field, int $bound) {
    expect(enforcedRules($property))->toContain($enforced)
        ->and(replacedThroughPipeline($property)->toParameter()->openApiAttributes[$field] ?? null)->toBe($bound);
})->with([
    'a later equal rule'                      => ['min_equal', 'min:5', 'minLength', 5],
    'a later rule of another class'           => ['min_other', 'min:5', 'minLength', 5],
    'an earlier Rule'                         => ['rule_then_min', 'min:5', 'minLength', 5],
    'an equal rule in a list'                 => ['max_in_list', 'max:10', 'maxLength', 10],
    're-added among other rules of its class' => ['min_re_added', 'min:5', 'minLength', 5],
]);

it('publishes the sentence of an attribute a later equal rule re-adds exactly once', function (string $property, string $enforced, string $sentence) {
    // Upstream drops the attribute and enforces the equal rule once; the rule
    // is documented in the attribute's place.
    expect(array_count_values(enforcedRules($property))[$enforced] ?? 0)->toBe(1)
        ->and(substr_count(replacedThroughPipeline($property)->description, $sentence))->toBe(1);
})->with([
    'size and bounds'         => ['min_equal', 'min:5', 'minimum <code>5</code>'],
    'cross-field acceptance'  => ['accepted_equal', 'accepted', 'Must be sent as one of'],
    'confirmation'            => ['confirmed_equal', 'confirmed', 'A matching'],
    'filled'                  => ['filled_equal', 'filled', 'When included, must not be empty.'],
    'prohibition'             => ['prohibited_equal', 'prohibited', 'Must not be sent; the request is rejected if it is.'],
    'conditional requirement' => ['required_if_equal', 'required_if:a,x', 'Required when <b><i>a</i></b> is'],
]);

class ReplacedRulesTestData extends Data
{
    public function __construct(
        #[Min(5), Rule('min:3')]
        public string $min_replaced,
        #[GreaterThan(5), Rule('gt:3')]
        public int $gt_replaced,
        #[Regex('/^a/'), Rule('regex:/^b/')]
        public string $regex_replaced,
        #[DateFormat('Y-m-d'), Rule('date_format:d/m/Y')]
        public string $date_format_replaced,
        #[Digits(3), Rule('digits:4')]
        public string $digits_replaced,
        #[Email, Rule('email:dns')]
        public string $email_replaced,
        #[Same('a'), Rule('same:b')]
        public string $same_replaced,
        #[RequiredIf('a', 'x'), Rule('required_if:b,y')]
        public ?string $required_if_replaced,
        #[Min(5), Rule('min:5')]
        public string $min_equal,
        #[Min(5), Rule('max:10')]
        public string $min_other,
        #[Rule('min:3'), Min(5)]
        public string $rule_then_min,
        #[Max(10), Rule('max:10|min:2')]
        public string $max_in_list,
        #[Accepted, Rule('accepted')]
        public bool $accepted_equal,
        #[Min(5), Rule('min:3|min:5')]
        public string $min_re_added,
        #[Confirmed, Rule('confirmed')]
        public string $confirmed_equal,
        #[Filled, Rule('filled')]
        public ?string $filled_equal,
        #[Prohibited, Rule('prohibited')]
        public ?string $prohibited_equal,
        #[RequiredIf('a', 'x'), Rule('required_if:a,x')]
        public ?string $required_if_equal,
        public string $a = '',
        public string $b = '',
    ) {}
}
