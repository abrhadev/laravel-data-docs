<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\AttributeProcessingStage;
use Spatie\LaravelData\Attributes\Validation\Alpha;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\AlphaNumeric;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\IPv4;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\Lowercase;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Attributes\Validation\Uppercase;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->stage = new AttributeProcessingStage();
});

it('processes validation attributes end-to-end', function ($propertyName, $expectedDescription, $expectedFormat) {
    $dataClass = app(DataConfig::class)->getDataClass(TestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === $propertyName);

    $context = new ParameterContext($property->name, $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toBe($expectedDescription);

    if ($expectedFormat) {
        expect($result->format)->toBe($expectedFormat);
    }
})->with([
    ['email', 'Must be a string. Must be a valid email address.', 'email'],
    ['website', 'Must be a string. Must be a valid URL.', 'uri'],
    ['uuid', 'Must be a string. Must be a valid UUID.', 'uuid'],
    ['password', 'Must be a string. Must be a valid password.', 'password'],
    ['ipv4', 'Must be a string. Must be a valid IPv4 address.', 'ipv4'],
    ['jsonData', 'Must be a string. Must be a valid JSON string.', 'json'],
    ['dateValue', 'Must be a string. Must be a valid date.', 'date'],
    ['ulid', 'Must be a string. Must be a valid ULID.', null],
]);

it('sets OpenAPI fields correctly', function ($propertyName, $expectedFields) {
    $dataClass = app(DataConfig::class)->getDataClass(TestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === $propertyName);

    $context = new ParameterContext($property->name, $property);
    $context->type = $expectedFields['type'] ?? 'string';

    $result = $this->stage->process($context);

    foreach ($expectedFields as $field => $value) {
        if ($field !== 'type') {
            expect($result->$field)->toBe($value, "Field {$field} should be {$value}");
        }
    }
})->with([
    ['username', ['type' => 'string', 'minLength' => 3]],
    ['title', ['type' => 'string', 'maxLength' => 100]],
    ['age', ['type' => 'integer', 'minimum' => 18]],
    ['score', ['type' => 'integer', 'exclusiveMinimum' => 0]],
]);

it('handles multiple validation attributes', function () {
    $testDataClass = new class ('test') extends Data {
        public function __construct(
            #[Min(3)]
            #[Max(50)]
            #[Alpha]
            public string $multiValidated,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testDataClass::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'multiValidated');

    $context = new ParameterContext($property->name, $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toContain('Must be a string.')
        ->and($result->description)->toContain('Must contain only letters.')
        ->and($result->description)->toContain('Must have minimum <code>3</code> characters.')
        ->and($result->description)->toContain('Must have maximum <code>50</code> characters.')
        ->and($result->minLength)->toBe(3)
        ->and($result->maxLength)->toBe(50)
        ->and($result->pattern)->toBe('^[a-zA-Z]+$');
});

it('combines descriptions properly', function () {
    $testDataClass = new class ('test@example.com') extends Data {
        public function __construct(
            #[Email]
            #[Min(5)]
            public string $email,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testDataClass::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'email');

    $context = new ParameterContext($property->name, $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string. Must be a valid email address. Must have minimum <code>5</code> characters.');
});

it('does not modify description when no validation attributes', function () {
    $testDataClass = new class ('test') extends Data {
        public function __construct(
            public string $plainField,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testDataClass::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'plainField');

    $context = new ParameterContext($property->name, $property);
    $context->description = 'Must be a string.';
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->description)->toBe('Must be a string.');
});

it('returns the same context instance', function () {
    $testDataClass = new class ('test') extends Data {
        public function __construct(
            public string $plainField,
        ) {}
    };

    $dataConfig = app(DataConfig::class);
    $dataClass = $dataConfig->getDataClass($testDataClass::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'plainField');

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

class TestData extends Data
{
    public function __construct(
        #[Email]
        public string $email,
        #[Url]
        public string $website,
        #[Uuid]
        public string $uuid,
        #[Alpha]
        public string $letters,
        #[AlphaDash]
        public string $slug,
        #[AlphaNumeric]
        public string $code,
        #[Lowercase]
        public string $lowercase,
        #[Uppercase]
        public string $uppercase,
        #[Json]
        public string $jsonData,
        #[Password]
        public string $password,
        #[IPv4]
        public string $ipv4,
        #[Ulid]
        public string $ulid,
        #[Min(3)]
        public string $username,
        #[Max(100)]
        public string $title,
        #[Min(18)]
        public int $age,
        #[Between(10, 500)]
        public string $description,
        #[Size(6)]
        public string $verificationCode,
        #[DateFormat('Y-m-d')]
        public string $birthDate,
        #[Digits(4)]
        public string $pinCode,
        #[GreaterThan(0)]
        public int $score,
        #[Date]
        public string $dateValue,
        #[Regex('/^[a-z]+$/')]
        public string $pattern,
        public string $plainField,
    ) {}
}
