<?php

use Abrha\LaravelDataDocs\AttributeProcessing\ReplacedRules;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\DataProperty;

function replacedRulesProperty(string $name): DataProperty
{
    return app(DataConfig::class)
        ->getDataClass(ReplacedRulesUnitTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $name);
}

function declaredMin(DataProperty $property): Min
{
    return array_values(array_filter(
        $property->attributes->all(Spatie\LaravelData\Support\Validation\ValidationRule::class),
        fn(object $attribute) => $attribute instanceof Min
    ))[0];
}

it('reports an attribute a later same-class Rule replaces', function () {
    $property = replacedRulesProperty('replaced');

    expect(ReplacedRules::isReplaced(declaredMin($property), $property))->toBeTrue();
});

it('reports an attribute a later equal rule re-adds, which is documented in its place', function () {
    $property = replacedRulesProperty('equal');

    expect(ReplacedRules::isReplaced(declaredMin($property), $property))->toBeTrue();
});

it('keeps an attribute a later rule of another class leaves alone', function () {
    $property = replacedRulesProperty('other_class');

    expect(ReplacedRules::isReplaced(declaredMin($property), $property))->toBeFalse();
});

it('keeps an attribute declared after the Rule', function () {
    $property = replacedRulesProperty('rule_first');

    expect(ReplacedRules::isReplaced(declaredMin($property), $property))->toBeFalse();
});

it('reports an attribute a later Rule re-adds among other rules of its class', function () {
    $property = replacedRulesProperty('re_added_in_list');

    expect(ReplacedRules::isReplaced(declaredMin($property), $property))->toBeTrue();
});

it('expands an unknown keyword to a plain Rule, which documents nothing', function () {
    $expanded = ReplacedRules::expand(new Rule('no_such_rule_keyword:1'));

    expect($expanded)->toHaveCount(1)
        ->and($expanded[0])->toBeInstanceOf(Rule::class)
        ->and($expanded[0]->get())->toBe(['no_such_rule_keyword:1']);
});

it('treats an attribute the property does not declare as declared first', function () {
    $property = replacedRulesProperty('replaced');

    expect(ReplacedRules::isReplaced(new Min(5), $property))->toBeTrue()
        ->and(ReplacedRules::isReplaced(new Max(9), $property))->toBeFalse();
});

it('matches upstream on a subclass of an attribute declared beside it', function (string $name, bool $minReplaced, bool $subMinReplaced, array $rules) {
    $property = replacedRulesProperty($name);
    $declared = $property->attributes->all(Spatie\LaravelData\Support\Validation\ValidationRule::class);
    $exactly = fn(string $class) => array_values(array_filter($declared, fn(object $attribute) => $attribute::class === $class))[0];

    expect(ReplacedRules::isReplaced($exactly(Min::class), $property))->toBe($minReplaced)
        ->and(ReplacedRules::isReplaced($exactly(ReplacedRulesUnitSubMin::class), $property))->toBe($subMinReplaced)
        ->and(ReplacedRulesUnitTestData::getValidationRules([])[$name])->toBe($rules);
})->with([
    // Upstream drops an earlier rule that is an instance of the one added, so a
    // later subclass leaves its parent alone while a later parent drops the subclass.
    'parent then subclass' => ['min_then_sub', false, false, ['required', 'string', 'min:3', 'min:5']],
    'subclass then parent' => ['sub_then_min', false, true, ['required', 'string', 'min:3']],
]);

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class ReplacedRulesUnitSubMin extends Min {}

class ReplacedRulesUnitTestData extends Data
{
    public function __construct(
        #[Min(5), Rule('min:3')]
        public string $replaced,
        #[Min(5), Rule('min:5')]
        public string $equal,
        #[Min(5), Rule('max:10')]
        public string $other_class,
        #[Rule('min:3'), Min(5)]
        public string $rule_first,
        #[Min(5), Rule('min:3|min:5')]
        public string $re_added_in_list,
        #[Min(3), ReplacedRulesUnitSubMin(5)]
        public string $min_then_sub,
        #[ReplacedRulesUnitSubMin(5), Min(3)]
        public string $sub_then_min,
    ) {}
}
