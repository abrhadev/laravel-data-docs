<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

// Upstream builds the enforced rule from keyword(), not from the attribute's
// class, so a consumer subclass that switches keyword is documented by the rule
// it emits: its condition sentence, required and nullable are each checked
// against Laravel's validator over payloads that turn the condition on and off.

function keywordSwitchedContext(string $property, string $class = KeywordSwitchedTestData::class): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass($class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(new ParameterContext($dataProperty->name, $dataProperty));
}

/**
 * @return array<int, array<string, string>>
 */
function keywordSwitchedPayloads(): array
{
    return [[], ['mode' => 'x'], ['mode' => 'y'], ['mode' => 'yes'], ['mode' => 'no'], ['mode' => 'x', 'other' => 'z']];
}

function keywordSwitchedPasses(string $property, array $payload): bool
{
    return validator($payload, [$property => KeywordSwitchedTestData::getValidationRules($payload)[$property]])->passes();
}

dataset('keyword-switched subclasses', [
    // [property, sentence or null]
    'RequiredIf switched to required_unless'            => ['unlessFromIf', 'Required unless <b><i>mode</i></b> is <code>x</code>.'],
    'RequiredIf switched to required_unless, int'       => ['unlessFromIfInt', 'Required unless <b><i>mode</i></b> is <code>x</code>.'],
    'RequiredIf switched to required_unless, string'    => ['unlessFromIfPlain', 'Required unless <b><i>mode</i></b> is <code>x</code>.'],
    'RequiredUnless switched to required_if, bool'      => ['ifFromUnless', 'Required when <b><i>mode</i></b> is <code>x</code>.'],
    'RequiredWith switched to required_without'         => ['withoutFromWith', 'Required when <b><i>mode</i></b> is not present.'],
    'RequiredWithout switched to required_with_all'     => ['withAllFromWithout', 'Required when both <b><i>mode</i></b> and <b><i>other</i></b> are present.'],
    'RequiredWith switched to required_without_all'     => ['withoutAllFromWith', 'Required when neither <b><i>mode</i></b> nor <b><i>other</i></b> is present.'],
    'RequiredIf switched to required'                   => ['plainRequired', null],
    'RequiredIf switched to required, int'              => ['plainRequiredInt', null],
    'RequiredIf switched to required_with'              => ['crossShape', null],
    'RequiredIf switched to required_if_accepted'       => ['ifAccepted', null],
    'RequiredIf switched to required_if_accepted, enum' => ['ifAcceptedEnum', null],
    'RequiredIf switched to required_if_accepted, str'  => ['ifAcceptedPlain', null],
    'RequiredIf switched to required_if_declined, bool' => ['ifDeclined', null],
    'RequiredIf switched to present_if'                 => ['presentIf', null],
    'RequiredWith switched to present_with, int'        => ['presentWith', null],
    'RequiredWith switched to missing_with'             => ['missingWith', null],
    'RequiredIf switched to missing'                    => ['missingKeyword', null],
    'RequiredIf switched to accepted_if, bool'          => ['acceptedIf', null],
    'RequiredIf switched to declined_if, bool'          => ['declinedIf', null],
    'RequiredIf switched to present'                    => ['presentKeyword', null],
    'RequiredIf switched to accepted, bool'             => ['acceptedKeyword', null],
    'RequiredIf switched to filled'                     => ['filledKeyword', null],
    'RequiredIf switched to a non-implicit rule, array' => ['nonImplicitKeyword', null],
]);

it('publishes the requirement of a keyword-switched subclass as Laravel enforces it', function (string $property) {
    $context = keywordSwitchedContext($property);
    $payloads = keywordSwitchedPayloads();

    $omissionAlwaysFails = array_filter($payloads, fn(array $payload) => keywordSwitchedPasses($property, $payload)) === [];
    $nullSometimesPasses = $context->property->type->isNullable
        && array_filter($payloads, fn(array $payload) => keywordSwitchedPasses($property, $payload + [$property => null])) !== [];

    expect($context->required)->toBe($omissionAlwaysFails, "{$property} required")
        ->and($context->nullable)->toBe($nullSometimesPasses, "{$property} nullable");
})->with('keyword-switched subclasses');

it('states the condition of a keyword-switched subclass only as the rule it emits', function (string $property, ?string $sentence) {
    $description = keywordSwitchedContext($property)->description ?? '';

    if ($sentence === null) {
        expect($description)->not->toContain('Required when')
            ->and($description)->not->toContain('Required unless')
            ->and($description)->not->toContain('Required depending');

        return;
    }

    expect($description)->toContain($sentence);
})->with('keyword-switched subclasses');

it('matches each stated condition with the validator', function (string $property, array $requiredWhen, array $optionalWhen) {
    expect(keywordSwitchedPasses($property, $requiredWhen))->toBeFalse()
        ->and(keywordSwitchedPasses($property, $optionalWhen))->toBeTrue();
})->with([
    'required_unless'      => ['unlessFromIf', ['mode' => 'y'], ['mode' => 'x']],
    'required_if'          => ['ifFromUnless', ['mode' => 'x'], ['mode' => 'y']],
    'required_without'     => ['withoutFromWith', [], ['mode' => 'y']],
    'required_with_all'    => ['withAllFromWithout', ['mode' => 'x', 'other' => 'z'], ['mode' => 'x']],
    'required_without_all' => ['withoutAllFromWith', [], ['mode' => 'x']],
]);

it('publishes a subclass whose keyword Laravel does not ship as required, since a custom rule may be implicit', function () {
    $context = keywordSwitchedContext('customKeyword', KeywordSwitchedUnknownTestData::class);

    expect($context->required)->toBeTrue()
        ->and($context->nullable)->toBeFalse()
        ->and($context->description ?? '')->not->toContain('Required when');
});

it('publishes a subclass whose keyword() throws as required, with no condition', function () {
    $context = keywordSwitchedContext('throwing', KeywordSwitchedUnknownTestData::class);

    expect($context->required)->toBeTrue()
        ->and($context->description ?? '')->not->toContain('Required when');
});

enum KeywordSwitchedStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToUnless extends RequiredIf
{
    public static function keyword(): string
    {
        return 'required_unless';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedUnlessToIf extends RequiredUnless
{
    public static function keyword(): string
    {
        return 'required_if';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedWithToWithout extends RequiredWith
{
    public static function keyword(): string
    {
        return 'required_without';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedWithoutToWithAll extends RequiredWithout
{
    public static function keyword(): string
    {
        return 'required_with_all';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedWithToWithoutAll extends RequiredWith
{
    public static function keyword(): string
    {
        return 'required_without_all';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToRequired extends RequiredIf
{
    public static function keyword(): string
    {
        return 'required';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToWith extends RequiredIf
{
    public static function keyword(): string
    {
        return 'required_with';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToIfAccepted extends RequiredIf
{
    public static function keyword(): string
    {
        return 'required_if_accepted';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToIfDeclined extends RequiredIf
{
    public static function keyword(): string
    {
        return 'required_if_declined';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToPresentIf extends RequiredIf
{
    public static function keyword(): string
    {
        return 'present_if';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedWithToPresentWith extends RequiredWith
{
    public static function keyword(): string
    {
        return 'present_with';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedWithToMissingWith extends RequiredWith
{
    public static function keyword(): string
    {
        return 'missing_with';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToMissing extends RequiredIf
{
    public static function keyword(): string
    {
        return 'missing';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToAcceptedIf extends RequiredIf
{
    public static function keyword(): string
    {
        return 'accepted_if';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToDeclinedIf extends RequiredIf
{
    public static function keyword(): string
    {
        return 'declined_if';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToPresent extends RequiredIf
{
    public static function keyword(): string
    {
        return 'present';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToAccepted extends RequiredIf
{
    public static function keyword(): string
    {
        return 'accepted';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToFilled extends RequiredIf
{
    public static function keyword(): string
    {
        return 'filled';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToArray extends RequiredIf
{
    public static function keyword(): string
    {
        return 'array';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToCustom extends RequiredIf
{
    public static function keyword(): string
    {
        return 'keyword_switched_custom';
    }
}

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedThrowing extends RequiredIf
{
    public static function keyword(): string
    {
        throw new RuntimeException('keyword unavailable');
    }
}

// A grandchild inherits its parent's switched keyword.
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class KeywordSwitchedIfToUnlessChild extends KeywordSwitchedIfToUnless {}

class KeywordSwitchedTestData extends Data
{
    public function __construct(
        public ?string $mode,
        public ?string $other,
        #[KeywordSwitchedIfToUnlessChild('mode', 'x')]
        public ?string $unlessFromIf,
        #[KeywordSwitchedIfToUnless('mode', 'x')]
        public ?int $unlessFromIfInt,
        #[KeywordSwitchedIfToUnless('mode', 'x')]
        public string $unlessFromIfPlain,
        #[KeywordSwitchedUnlessToIf('mode', 'x')]
        public ?bool $ifFromUnless,
        #[KeywordSwitchedWithToWithout('mode')]
        public ?string $withoutFromWith,
        #[KeywordSwitchedWithoutToWithAll('mode', 'other')]
        public ?float $withAllFromWithout,
        #[KeywordSwitchedWithToWithoutAll('mode', 'other')]
        public ?array $withoutAllFromWith,
        #[KeywordSwitchedIfToRequired('mode', 'x')]
        public ?string $plainRequired,
        #[KeywordSwitchedIfToRequired('mode', 'x')]
        public int $plainRequiredInt,
        #[KeywordSwitchedIfToWith('mode', 'x')]
        public ?string $crossShape,
        #[KeywordSwitchedIfToIfAccepted('mode')]
        public ?string $ifAccepted,
        #[KeywordSwitchedIfToIfAccepted('mode')]
        public ?KeywordSwitchedStatus $ifAcceptedEnum,
        #[KeywordSwitchedIfToIfAccepted('mode')]
        public string $ifAcceptedPlain,
        #[KeywordSwitchedIfToIfDeclined('mode')]
        public ?bool $ifDeclined,
        #[KeywordSwitchedIfToPresentIf('mode', 'x')]
        public ?string $presentIf,
        #[KeywordSwitchedWithToPresentWith('mode')]
        public ?int $presentWith,
        #[KeywordSwitchedWithToMissingWith('mode')]
        public ?string $missingWith,
        #[KeywordSwitchedIfToMissing('mode', 'x')]
        public ?string $missingKeyword,
        #[KeywordSwitchedIfToAcceptedIf('mode', 'x')]
        public ?bool $acceptedIf,
        #[KeywordSwitchedIfToDeclinedIf('mode', 'x')]
        public ?bool $declinedIf,
        #[KeywordSwitchedIfToPresent('mode', 'x')]
        public ?string $presentKeyword,
        #[KeywordSwitchedIfToAccepted('mode', 'x')]
        public ?bool $acceptedKeyword,
        #[KeywordSwitchedIfToFilled('mode', 'x')]
        public ?string $filledKeyword,
        #[KeywordSwitchedIfToArray('mode', 'x')]
        public ?array $nonImplicitKeyword,
    ) {}
}

class KeywordSwitchedUnknownTestData extends Data
{
    public function __construct(
        #[KeywordSwitchedIfToCustom('mode', 'x')]
        public ?string $customKeyword,
        #[KeywordSwitchedThrowing('mode', 'x')]
        public ?string $throwing,
    ) {}
}
