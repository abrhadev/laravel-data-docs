<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Spatie\LaravelData\Attributes\Validation\Exclude;
use Spatie\LaravelData\Attributes\Validation\ExcludeIf;
use Spatie\LaravelData\Attributes\Validation\ExcludeUnless;
use Spatie\LaravelData\Attributes\Validation\ExcludeWith;
use Spatie\LaravelData\Attributes\Validation\ExcludeWithout;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Prohibited;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Attributes\Validation\ProhibitedUnless;
use Spatie\LaravelData\Attributes\Validation\Prohibits;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

function neverSatisfiableSentence(): string
{
    return 'Note: this field is both required and prohibited, so no request can pass validation.';
}

function prohibitionThroughPipeline(string $property, string $class = ProhibitionExclusionTestData::class): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass($class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

it('AC1: documents an unconditionally prohibited field as optional and forbidden', function () {
    $context = prohibitionThroughPipeline('internal_ref');

    expect($context->required)->toBeFalse()
        ->and($context->description)->toContain('Must not be sent; the request is rejected if it is.');
});

it('AC2: states a value-conditional prohibition', function () {
    expect(prohibitionThroughPipeline('trial_ends_at')->description)->toContain(
        'Must not be sent when <b><i>plan</i></b> is <code>enterprise</code>; the request is rejected if it is.'
    );
});

it('AC3: states an inverted value-conditional prohibition', function () {
    expect(prohibitionThroughPipeline('override_reason')->description)->toContain(
        'Must not be sent unless <b><i>role</i></b> is <code>admin</code>; the request is rejected if it is.'
    );
});

it('AC4: states that sending the field forbids the others', function () {
    expect(prohibitionThroughPipeline('full_refund')->description)->toContain(
        'Sending this field forbids sending <b><i>partial_amount</i></b> and <b><i>refund_items</i></b>; the request is rejected if any of them is also sent.'
    );
});

it('AC5: keeps an unconditionally excluded field listed and states its exclusion', function () {
    $parameters = (new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class)))(
        ProhibitionExclusionTestData::class
    );

    expect($parameters)->toHaveKey('legacy_token')
        ->and($parameters['legacy_token']->description)->toContain('Not validated, and removed from the validated input.');
});

it('AC6: states value-conditional exclusions', function () {
    expect(prohibitionThroughPipeline('promo_code')->description)->toContain(
        'Not validated, and removed from the validated input when <b><i>order_type</i></b> is <code>internal</code>.'
    )
        ->and(prohibitionThroughPipeline('manual_price')->description)->toContain(
            'Not validated, and removed from the validated input unless <b><i>pricing_mode</i></b> is <code>manual</code>.'
        );
});

it('AC7: states presence-conditional exclusions', function () {
    expect(prohibitionThroughPipeline('estimated_total')->description)->toContain(
        'Not validated, and removed from the validated input when <b><i>confirmed_total</i></b> is present.'
    )
        ->and(prohibitionThroughPipeline('default_currency')->description)->toContain(
            'Not validated, and removed from the validated input when <b><i>country</i></b> is not present.'
        );
});

it('AC8: words rejection and removal distinguishably', function () {
    $prohibited = prohibitionThroughPipeline('trial_ends_at')->description;
    $excluded = prohibitionThroughPipeline('excluded_on_plan')->description;
    $condition = 'when <b><i>plan</i></b> is <code>enterprise</code>';

    expect($prohibited)->toContain('rejected')
        ->and($prohibited)->toContain('Must not')
        ->and($prohibited)->toContain($condition)
        ->and($excluded)->toContain($condition)
        ->and(preg_match('/Not validated[^.]*\./', $excluded, $match))->toBe(1);

    // The type sentence "Must be a string." precedes it, so only the exclusion sentence is checked.
    foreach (['rejected', 'ignored', 'discarded', 'dropped', 'must'] as $word) {
        expect(strtolower($match[0]))->not->toContain($word);
    }
});

it('AC9: produces one coherent description in a stable order', function () {
    expect(prohibitionThroughPipeline('discount_code')->description)->toBe(
        'Must be a string. Must not be sent when <b><i>order_type</i></b> is <code>internal</code>; the request is rejected if it is. A null value is accepted.'
    );
});

it('AC9: repeats byte-identically across successive builds', function () {
    expect(prohibitionThroughPipeline('discount_code')->description)
        ->toBe(prohibitionThroughPipeline('discount_code')->description);
});

it('AC10: a dangling field reference does not break the build', function () {
    $parameters = (new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class)))(
        ProhibitionExclusionTestData::class
    );

    expect($parameters['dangling']->description)->toContain('Must not be sent when <b><i>missing_field</i></b> is <code>x</code>')
        ->and($parameters['sibling']->description)->toBe('Must be a string. A null value is accepted.');
});

it('does not suppress a prohibition declared before Required, which the API still enforces', function () {
    $context = prohibitionThroughPipeline('x', ProhibitionThenRequiredTestData::class);

    expect($context->required)->toBeTrue()
        ->and($context->description)->toContain(
            'Must not be sent when <b><i>plan</i></b> is <code>enterprise</code>; the request is rejected if it is.'
        );
});

it('warns when a required field is also unconditionally prohibited', function () {
    $context = prohibitionThroughPipeline('locked', NeverSatisfiableTestData::class);

    expect($context->required)->toBeTrue()
        ->and($context->description)->toEndWith(neverSatisfiableSentence());
});

it('does not warn where some request can satisfy the field', function (string $property) {
    expect(prohibitionThroughPipeline($property, NeverSatisfiableTestData::class)->description)
        ->not->toContain(neverSatisfiableSentence());
})->with([
    'defaulted'             => ['defaulted'],
    'present, may be empty' => ['present_prohibited'],
    'conditional'           => ['conditional'],
    'excluded'              => ['excluded'],
    'wrapped rule'          => ['wrapped'],
]);

it('withholds the warning when an earlier exclusion lets a request pass', function (string $class, array $passingPayload) {
    // Laravel stops validating a field once it is excluded, so Prohibited never runs.
    $class::validate($passingPayload);

    expect(prohibitionThroughPipeline('x', $class)->description)->not->toContain(neverSatisfiableSentence());
})->with([
    'Exclude first'   => [ExcludedBeforeProhibitionTestData::class, ['x' => 'v']],
    'ExcludeIf first' => [ConditionallyExcludedBeforeProhibitionTestData::class, ['mode' => 'legacy', 'x' => 'v']],
]);

it('keeps the warning when the exclusion comes after the prohibition', function () {
    // Prohibited runs first and rejects a sent value; omitting it fails Required.
    foreach ([['x' => 'v'], []] as $payload) {
        expect(fn() => ProhibitionBeforeExclusionTestData::validate($payload))
            ->toThrow(Illuminate\Validation\ValidationException::class);
    }

    expect(prohibitionThroughPipeline('x', ProhibitionBeforeExclusionTestData::class)->description)
        ->toEndWith(neverSatisfiableSentence());
});

it('states a prohibition wrapping a rule object as conditional', function () {
    expect(prohibitionThroughPipeline('wrapped', NeverSatisfiableTestData::class)->description)
        ->toContain('Must not be sent in some requests; the request is rejected if it is.');
});

class ProhibitionExclusionTestData extends Data
{
    public function __construct(
        #[Prohibited]
        public ?string $internal_ref = null,
        #[ProhibitedIf('plan', 'enterprise')]
        public ?string $trial_ends_at = null,
        #[ProhibitedUnless('role', 'admin')]
        public ?string $override_reason = null,
        #[Prohibits(['partial_amount', 'refund_items'])]
        public ?bool $full_refund = null,
        #[Exclude]
        public ?string $legacy_token = null,
        #[ExcludeIf('order_type', 'internal')]
        public ?string $promo_code = null,
        #[ExcludeUnless('pricing_mode', 'manual')]
        public ?string $manual_price = null,
        #[ExcludeWith('confirmed_total')]
        public ?int $estimated_total = null,
        #[ExcludeWithout('country')]
        public ?string $default_currency = null,
        #[ExcludeIf('plan', 'enterprise')]
        public ?string $excluded_on_plan = null,
        #[ProhibitedIf('order_type', 'internal')]
        #[Nullable]
        public ?string $discount_code = null,
        #[ProhibitedIf('missing_field', 'x')]
        public ?string $dangling = null,
        public ?string $sibling = null,
    ) {}
}

class ProhibitionThenRequiredTestData extends Data
{
    public function __construct(
        #[ProhibitedIf('plan', 'enterprise')]
        #[Required]
        public string $x,
    ) {}
}

class NeverSatisfiableTestData extends Data
{
    public function __construct(
        #[Prohibited]
        public string $locked,
        #[Present]
        #[Prohibited]
        public ?string $present_prohibited,
        #[ProhibitedIf('plan', 'enterprise')]
        public string $conditional,
        #[Exclude]
        public string $excluded,
        #[Prohibited(new \Illuminate\Validation\Rules\ProhibitedIf(true))]
        public string $wrapped,
        #[Prohibited]
        public string $defaulted = 'x',
    ) {}
}

class ExcludedBeforeProhibitionTestData extends Data
{
    public function __construct(
        #[Exclude]
        #[Prohibited]
        public string $x,
    ) {}
}

class ConditionallyExcludedBeforeProhibitionTestData extends Data
{
    public function __construct(
        public string $mode,
        #[ExcludeIf('mode', 'legacy')]
        #[Prohibited]
        public string $x,
    ) {}
}

class ProhibitionBeforeExclusionTestData extends Data
{
    public function __construct(
        #[Prohibited]
        #[Exclude]
        public string $x,
    ) {}
}
