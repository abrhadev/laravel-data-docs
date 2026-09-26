<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\NotIn;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

enum NotInTestRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case Viewer = 'viewer';
}

function notInThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(NotInTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function passesNotInRules(string $property, mixed $value): bool
{
    $rules = NotInTestData::getValidationRules([])[$property];

    return Validator::make([$property => $value], [$property => $rules])->passes();
}

it('states the rejected values and publishes no allowed set (AC4)', function () {
    $context = notInThroughPipeline('username');

    expect($context->toParameter()->enumValues)->toBeNull()
        ->and($context->description)->toContain('Must not be one of: <code>admin</code>, <code>root</code>, <code>system</code>.');

    foreach (['admin', 'root', 'system'] as $rejected) {
        expect(passesNotInRules('username', $rejected))->toBeFalse();
    }

    expect(passesNotInRules('username', 'bob'))->toBeTrue();
});

it('publishes the allowed set without the rejected values, in either order (AC7)', function (string $property) {
    $context = notInThroughPipeline($property);

    expect($context->toParameter()->enumValues)->toBe(['pending', 'shipped'])
        ->and($context->description)->toContain('Must be one of: <code>pending</code>, <code>shipped</code>.')
        ->not->toContain('cancelled')
        ->and(passesNotInRules($property, 'cancelled'))->toBeFalse()
        ->and(passesNotInRules($property, 'pending'))->toBeTrue()
        ->and(passesNotInRules($property, 'shipped'))->toBeTrue();
})->with(['in_then_not', 'not_then_in']);

it('drops a rejected case from an enum-typed property', function () {
    $context = notInThroughPipeline('role');

    expect($context->toParameter()->enumValues)->toBe(['editor', 'viewer'])
        ->and($context->description)->not->toContain('Admin')
        ->and(passesNotInRules('role', 'admin'))->toBeFalse()
        ->and(passesNotInRules('role', 'editor'))->toBeTrue();
});

it('states on an array that at least one item must not be rejected', function () {
    $context = notInThroughPipeline('tags');

    expect($context->description)->toContain('Must include at least one item that is not one of: <code>admin</code>.')
        ->and(passesNotInRules('tags', ['admin']))->toBeFalse()
        ->and(passesNotInRules('tags', ['admin', 'bob']))->toBeTrue();
});

it('keeps an array item set that an array NotIn does not narrow', function () {
    $context = notInThroughPipeline('roles');

    expect($context->toParameter()->enumValues)->toBe(['admin', 'editor'])
        ->and($context->description)->toContain('Must include at least one item that is not one of: <code>admin</code>.')
        ->and(passesNotInRules('roles', ['admin', 'editor']))->toBeTrue()
        ->and(passesNotInRules('roles', ['admin']))->toBeFalse();
});

it('documents a not_in rule string as the attribute', function () {
    expect(notInThroughPipeline('from_rule')->description)->toContain('Must not be one of: <code>a</code>, <code>b</code>.')
        ->and(passesNotInRules('from_rule', 'a'))->toBeFalse();
});

it('publishes an empty set when NotIn rejects every allowed value', function () {
    expect(notInThroughPipeline('nothing_left')->description)->toContain('Note: the list of allowed values is empty')
        ->and(notInThroughPipeline('nothing_left')->toParameter()->enumValues)->toBeNull()
        ->and(passesNotInRules('nothing_left', 'a'))->toBeFalse();
});

it('states only the values a boolean field can hold, which fails its own rule for any other', function () {
    expect(notInThroughPipeline('flag')->description)->toContain('Must not be one of: <code>"0"</code>.')
        ->and(passesNotInRules('flag', 'no'))->toBeFalse()
        ->and(passesNotInRules('flag', '0'))->toBeFalse()
        ->and(passesNotInRules('flag', false))->toBeTrue();
});

it('gives an example the rules accept', function (string $property) {
    foreach (range(1, 25) as $run) {
        expect(passesNotInRules($property, notInThroughPipeline($property)->example))->toBeTrue();
    }
})->with(['username', 'in_then_not', 'not_then_in', 'role', 'tags', 'roles', 'from_rule', 'count']);

class NotInTestData extends Data
{
    /**
     * @param string[] $tags
     * @param string[] $roles
     */
    public function __construct(
        #[NotIn(['admin', 'root', 'system'])]
        public string $username,
        #[In(['pending', 'shipped', 'cancelled']), NotIn(['cancelled'])]
        public string $in_then_not,
        #[NotIn(['cancelled']), In(['pending', 'shipped', 'cancelled'])]
        public string $not_then_in,
        #[NotIn(['admin'])]
        public NotInTestRole $role,
        #[NotIn(['admin'])]
        public array $tags,
        #[In(['admin', 'editor']), NotIn(['admin'])]
        public array $roles,
        #[Rule('not_in:a,b')]
        public string $from_rule,
        #[In(['a']), NotIn(['a'])]
        public string $nothing_left,
        #[NotIn([1, 2, 3, 4, 5, 6, 7, 8, 9, 10])]
        public int $count,
        #[NotIn(['no', '0'])]
        public bool $flag,
    ) {}
}
