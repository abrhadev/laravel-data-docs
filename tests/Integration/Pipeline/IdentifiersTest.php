<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EmailProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\PasswordProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\UrlProcessor;
use Abrha\LaravelDataDocs\Attributes\Example;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\LaravelData\Attributes\Validation\ActiveUrl;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\IP;
use Spatie\LaravelData\Attributes\Validation\IPv4;
use Spatie\LaravelData\Attributes\Validation\IPv6;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\NotIn;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

afterEach(function () {
    PasswordRule::$defaultCallback = null;

    if (method_exists(Factory::class, 'fakeDnsLookups')) {
        Validator::fakeDnsLookups(false);
    }
});

function identifierThroughPipeline(string $property): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(IdentifiersTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return PipelineFactory::createDefault()->process(
        new ParameterContext($dataProperty->name, $dataProperty)
    );
}

function identifierContext(string $type = 'string'): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(IdentifiersTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === 'url');

    $context = new ParameterContext('field', $dataProperty);
    $context->type = $type;

    return $context;
}

/**
 * Validates the property's generated example against the Data class's own rules
 * over repeated runs, since each run draws a new example.
 */
function expectIdentifierExamplesPass(string $property): void
{
    $rules = IdentifiersTestData::getValidationRules([])[$property];

    foreach (range(1, 25) as $run) {
        $example = identifierThroughPipeline($property)->example;
        $validator = Validator::make([$property => $example], [$property => $rules]);

        expect($validator->passes())->toBeTrue("Example " . json_encode($example) . " failed: " . json_encode($validator->errors()->all()));
    }
}

it('states the arguments of Url, Email and Password in the sentence', function (string $property, string $sentence) {
    expect(identifierThroughPipeline($property)->description)->toBe("Must be a string. {$sentence}");
})->with([
    'url'                      => ['url', 'Must be a valid URL.'],
    'url, one protocol'        => ['https', 'Must be a valid URL using the <code>https</code> protocol.'],
    'url, two protocols'       => ['ftp', 'Must be a valid URL using one of the protocols: <code>ftp</code>, <code>sftp</code>.'],
    'active url'               => ['active', 'Must be an active URL.'],
    'json'                     => ['json', 'Must be a valid JSON string.'],
    'email'                    => ['email', 'Must be a valid email address.'],
    'email, rfc only'          => ['emailRfc', 'Must be a valid email address.'],
    'email, rfc and dns'       => ['emailDns', 'Must be a valid email address that passes RFC 5322 validation and a DNS check that its domain has an MX, A or AAAA record.'],
    'email, strict and filter' => ['emailStrict', "Must be a valid email address that passes strict RFC 5322 validation, which also rejects addresses with warnings and PHP's <code>FILTER_VALIDATE_EMAIL</code> filter."],
    'email, three modes'       => ['emailSpoof', 'Must be a valid email address that passes RFC 5322 validation, a spoofing check that rejects addresses mixing Unicode scripts and a DNS check that its domain has an MX, A or AAAA record.'],
    'password'                 => ['password', 'Must be a password of at least <code>12</code> characters.'],
    'password, every class'    => ['passwordStrong', 'Must be a password of at least <code>24</code> characters containing at least one uppercase and one lowercase letter, at least one number and at least one symbol.'],
    'password, letters'        => ['passwordLetters', 'Must be a password of at least <code>12</code> characters containing at least one letter.'],
    'password, above 20'       => ['passwordLong', 'Must be a password of at least <code>32</code> characters containing at least one number.'],
    'password, minimum 0'      => ['passwordZero', 'Must be a password of at least <code>1</code> character.'],
    'password, uncompromised'  => ['passwordUncompromised', 'Must be a password of at least <code>12</code> characters. Must not appear in a known data leak.'],
    'password, threshold'      => ['passwordThreshold', 'Must be a password of at least <code>12</code> characters. Must not appear in known data leaks more than <code>3</code> times.'],
]);

it('documents a default password rule as the application configures it', function () {
    PasswordRule::defaults(fn() => PasswordRule::min(10)->mixedCase()->numbers());

    expect(identifierThroughPipeline('passwordDefault')->description)
        ->toBe('Must be a string. Must be a password of at least <code>10</code> characters containing at least one uppercase and one lowercase letter and at least one number.')
        ->and(identifierThroughPipeline('passwordDefault')->minLength)->toBe(10);
});

it('documents a default password rule as Laravel defines it when the application sets none', function () {
    expect(identifierThroughPipeline('passwordDefault')->description)
        ->toBe('Must be a string. Must be a password of at least <code>8</code> characters.');
});

it('publishes a password minimum as minLength, the strictest one whatever the order', function (string $property, int $minLength) {
    $context = identifierThroughPipeline($property);

    expect($context->format)->toBe('password')
        ->and($context->toParameter()->openApiAttributes['minLength'] ?? null)->toBe($minLength);
})->with([
    'password'              => ['password', 12],
    'password 24'           => ['passwordStrong', 24],
    'password, then Min 20' => ['passwordThenMin', 20],
    'Min 20, then password' => ['minThenPassword', 20],
    'Min 8, then password'  => ['lowMinThenPassword', 12],
]);

it('states the IP, UUID and ULID rules and publishes a format only where it matches exactly', function (string $property, string $sentence, ?string $format) {
    $context = identifierThroughPipeline($property);

    expect($context->description)->toBe("Must be a string. {$sentence}")
        ->and($context->toParameter()->openApiAttributes['format'] ?? null)->toBe($format);
})->with([
    'ip'   => ['ip', 'Must be a valid IP address.', null],
    'ipv4' => ['ipv4', 'Must be a valid IPv4 address.', 'ipv4'],
    'ipv6' => ['ipv6', 'Must be a valid IPv6 address.', 'ipv6'],
    'uuid' => ['uuid', 'Must be a valid UUID.', 'uuid'],
    'ulid' => ['ulid', 'Must be a valid ULID.', null],
]);

it('publishes a ULID pattern that accepts exactly what the ulid rule accepts', function (string $value, bool $valid) {
    $pattern = identifierThroughPipeline('ulid')->pattern;
    $rules = IdentifiersTestData::getValidationRules([])['ulid'];

    expect(Validator::make(['ulid' => $value], ['ulid' => $rules])->passes())->toBe($valid)
        ->and(preg_match("/{$pattern}/u", $value) === 1)->toBe($valid);
})->with([
    'upper case'        => ['01ARZ3NDEKTSV4RRFFQ69G5FAV', true],
    'lower case'        => ['01arz3ndektsv4rrffq69g5fav', true],
    'first character 7' => ['7ZZZZZZZZZZZZZZZZZZZZZZZZZ', true],
    'first character 8' => ['8ZZZZZZZZZZZZZZZZZZZZZZZZZ', false],
    'excluded letter'   => ['01ARZ3NDEKTSV4RRFFQ69G5FAI', false],
    'too short'         => ['01ARZ3NDEKTSV4RRFFQ69G5FA', false],
]);

it('keeps the uri, email and json formats', function (string $property, string $format) {
    expect(identifierThroughPipeline($property)->format)->toBe($format);
})->with([
    ['url', 'uri'],
    ['https', 'uri'],
    ['active', 'uri'],
    ['email', 'email'],
    ['emailDns', 'email'],
    ['json', 'json'],
]);

it('generates examples that pass the Data class\'s own rules', function (string $property) {
    expectIdentifierExamplesPass($property);
})->with([
    'uuid'                     => ['uuid'],
    'ulid'                     => ['ulid'],
    'ip'                       => ['ip'],
    'ipv4'                     => ['ipv4'],
    'ipv6'                     => ['ipv6'],
    'url'                      => ['url'],
    'url, https'               => ['https'],
    'url, ftp or sftp'         => ['ftp'],
    'url, http'                => ['http'],
    'url, upper-case HTTPS'    => ['upperHttps'],
    'url, ftp and a pattern'   => ['ftpWithPattern'],
    'url, http and Max'        => ['httpWithMax'],
    'url and Min'              => ['urlWithMin'],
    'url, ftp and NotIn'       => ['ftpWithNotIn'],
    'json'                     => ['json'],
    'email'                    => ['email'],
    'email, strict and filter' => ['emailStrict'],
    'password'                 => ['password'],
    'password, every class'    => ['passwordStrong'],
    'password, letters'        => ['passwordLetters'],
    'password, above 20'       => ['passwordLong'],
    'password, with Max 14'    => ['passwordMax'],
    'password, then Min 20'    => ['passwordThenMin'],
    'Min 20, then password'    => ['minThenPassword'],
]);

// active_url resolves the host; the suite has no network, so Laravel's fake DNS
// lookup stands in (it accepts any valid hostname). The example hosts
// example.com, .org and .net do resolve on the real network. The fake arrived
// in Laravel 13.22, so older versions skip this test.
it('generates an active URL example that passes with DNS faked', function () {
    Validator::fakeDnsLookups();

    expectIdentifierExamplesPass('active');
})->skip(! method_exists(Factory::class, 'fakeDnsLookups'), 'Validator::fakeDnsLookups() needs Laravel 13.22 or later');

it('generates a password example that passes a default rule the application configures', function () {
    PasswordRule::defaults(fn() => PasswordRule::min(16)->letters()->mixedCase()->numbers()->symbols());

    expectIdentifierExamplesPass('passwordDefault');
});

it('does not replace an explicit example with a protocol example', function () {
    expect(identifierThroughPipeline('ftpWithExample')->example)->toBe('sftp://files.example.com/a');
});

it('records the scheme for the stage and leaves the example to it', function () {
    $context = identifierContext();

    (new UrlProcessor())->process(new Url('ftp'), $context);

    expect($context->uriScheme)->toBe('ftp')
        ->and($context->example)->toBeNull()
        ->and(identifierThroughPipeline('http')->example)->toStartWith('http://example.');
});

it('records a scheme only for a string field', function () {
    $context = identifierContext('string[]');

    (new UrlProcessor())->process(new Url('ftp'), $context);

    expect($context->uriScheme)->toBeNull();
});

it('records no scheme for a protocol Laravel reads as a regex', function () {
    $context = identifierContext();

    (new UrlProcessor())->process(new Url('coap+tcp'), $context);

    expect($context->uriScheme)->toBeNull()
        ->and($context->descriptions)->toBe(['Must be a valid URL using the <code>coap+tcp</code> protocol.']);
});

it('falls back to the generic sentence for an argument resolved at request time', function (object $processor, object $attribute, string $sentence, string $format) {
    $context = identifierContext();

    $processor->process($attribute, $context);

    expect($context->descriptions)->toBe([$sentence])
        ->and($context->format)->toBe($format)
        ->and($context->minLength)->toBeNull()
        ->and($context->example)->toBeNull();
})->with([
    'url protocol'     => fn() => [new UrlProcessor(), new Url(new RouteParameterReference('scheme')), 'Must be a valid URL.', 'uri'],
    'email mode'       => fn() => [new EmailProcessor(), new Email(new RouteParameterReference('mode')), 'Must be a valid email address.', 'email'],
    'password minimum' => fn() => [new PasswordProcessor(), new Password(min: new RouteParameterReference('length')), 'Must be a valid password.', 'password'],
    'password flag'    => fn() => [new PasswordProcessor(), new Password(numbers: new RouteParameterReference('digits')), 'Must be a valid password.', 'password'],
]);

it('falls back to the generic sentence when no email mode is valid', function () {
    $context = identifierContext();

    (new EmailProcessor())->process(new Email('bogus'), $context);

    expect($context->descriptions)->toBe(['Must be a valid email address.']);
});

it('documents a password rule object, with its maximum and custom rules', function () {
    $context = identifierContext();
    $context->maxLength = 50;

    $rule = PasswordRule::min(10)->max(64)->letters()->symbols()->uncompromised(5)->rules(['not_in:password1234']);
    (new PasswordProcessor())->process(new Password(rule: $rule), $context);

    expect($context->descriptions)->toBe(['Must be a password of <code>10</code> to <code>64</code> characters containing at least one letter and at least one symbol. Must not appear in known data leaks more than <code>5</code> times. Also subject to custom password rules.'])
        ->and($context->minLength)->toBe(10)
        ->and($context->maxLength)->toBe(50);
});

it('narrows an #[In] set to the addresses the IP version rule accepts', function (string $property, array $published, array $dropped) {
    $rules = IdentifiersTestData::getValidationRules([])[$property];

    expect(identifierThroughPipeline($property)->toParameter()->enumValues)->toBe($published);

    foreach ($published as $value) {
        expect(Validator::make([$property => $value], [$property => $rules])->passes())->toBeTrue();
    }

    foreach ($dropped as $value) {
        expect(Validator::make([$property => $value], [$property => $rules])->fails())->toBeTrue();
    }
})->with([
    'ipv4' => ['ipv4InSet', ['10.0.0.1'], ['::1', 'bad']],
    'ipv6' => ['ipv6InSet', ['::1'], ['10.0.0.1', 'bad']],
]);

class IdentifiersTestData extends Data
{
    public function __construct(
        #[Uuid]
        public string $uuid,
        #[Ulid]
        public string $ulid,
        #[IP]
        public string $ip,
        #[IPv4]
        public string $ipv4,
        #[IPv6]
        public string $ipv6,
        #[In(['10.0.0.1', '::1', 'bad']), IPv4]
        public string $ipv4InSet,
        #[In(['10.0.0.1', '::1', 'bad']), IPv6]
        public string $ipv6InSet,
        #[Url]
        public string $url,
        #[Url('https')]
        public string $https,
        #[Url(['ftp', 'sftp'])]
        public string $ftp,
        #[Url('http')]
        public string $http,
        #[Url('HTTPS')]
        public string $upperHttps,
        #[Example('sftp://files.example.com/a'), Url('ftp', 'sftp')]
        public string $ftpWithExample,
        #[Url('ftp'), StartsWith('ftp://example.org/')]
        public string $ftpWithPattern,
        #[Url('http'), Max(30)]
        public string $httpWithMax,
        #[Url, Min(40)]
        public string $urlWithMin,
        #[Url('ftp'), NotIn(['ftp://example.com/']), Max(30)]
        public string $ftpWithNotIn,
        #[ActiveUrl]
        public string $active,
        #[Json]
        public string $json,
        #[Email]
        public string $email,
        #[Email('rfc')]
        public string $emailRfc,
        #[Email('rfc', 'dns')]
        public string $emailDns,
        #[Email('strict', 'filter')]
        public string $emailStrict,
        #[Email('rfc', 'spoof', 'dns', 'rfc')]
        public string $emailSpoof,
        #[Password]
        public string $password,
        #[Password(min: 24, letters: true, mixedCase: true, numbers: true, symbols: true)]
        public string $passwordStrong,
        #[Password(letters: true)]
        public string $passwordLetters,
        #[Password(min: 32, numbers: true)]
        public string $passwordLong,
        #[Password(min: 0)]
        public string $passwordZero,
        #[Password(uncompromised: true)]
        public string $passwordUncompromised,
        #[Password(uncompromised: true, uncompromisedThreshold: 3)]
        public string $passwordThreshold,
        #[Password(default: true)]
        public string $passwordDefault,
        #[Password(numbers: true, symbols: true), Max(14)]
        public string $passwordMax,
        #[Password(min: 16), Min(20)]
        public string $passwordThenMin,
        #[Min(20), Password(min: 16)]
        public string $minThenPassword,
        #[Min(8), Password]
        public string $lowMinThenPassword,
    ) {}
}
