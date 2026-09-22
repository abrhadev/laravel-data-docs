<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\RequiredStage;
use Abrha\LaravelDataDocs\Pipeline\Support\RequirementResolver;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $this->dataConfig = app(DataConfig::class);
    $this->stage = new RequiredStage(RequirementResolver::fromConfig());
});

it('sets required to true when not nullable, not optional, and no default', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'fullyRequired');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeTrue();
});

it('sets required to false when nullable (even without optional or default)', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyNullable');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse();
});

it('sets required to false when optional (even without nullable or default)', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyOptional');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse();
});

it('sets required to false when has default (even without nullable or optional)', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyDefault');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse();
});

it('sets required to false when nullable and has default', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'nullableWithDefault');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse();
});

it('sets required to false when optional and has default', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'optionalWithDefault');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse();
});

it('sets required to false when nullable and optional', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'nullableAndOptional');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse()
        ->and($result->nullable)->toBeTrue()
        ->and($result->onlyValidatedWhenPresent)->toBeTrue();
});

it('sets required to false when nullable, optional, and has default', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'allThree');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->required)->toBeFalse()
        ->and($result->nullable)->toBeTrue()
        ->and($result->onlyValidatedWhenPresent)->toBeTrue();
});

it('sets nullable to false when property type is not nullable', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'fullyRequired');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->nullable)->toBeFalse();
});

it('sets nullable to true when property type is nullable', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyNullable');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->nullable)->toBeTrue();
});

it('sets nullable independently of optional', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);

    $optionalButNotNullable = $dataClass->properties->first(fn($p) => $p->name === 'onlyOptional');
    $context1 = new ParameterContext($optionalButNotNullable->name, $optionalButNotNullable);
    $result1 = $this->stage->process($context1);

    expect($result1->nullable)->toBeFalse();
});

it('sets nullable independently of hasDefaultValue', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);

    $defaultButNotNullable = $dataClass->properties->first(fn($p) => $p->name === 'onlyDefault');
    $context1 = new ParameterContext($defaultButNotNullable->name, $defaultButNotNullable);
    $result1 = $this->stage->process($context1);

    $nullableWithDefault = $dataClass->properties->first(fn($p) => $p->name === 'nullableWithDefault');
    $context2 = new ParameterContext($nullableWithDefault->name, $nullableWithDefault);
    $result2 = $this->stage->process($context2);

    expect($result1->nullable)->toBeFalse()
        ->and($result2->nullable)->toBeTrue();
});

it('sets both nullable and required correctly for non-nullable required field', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'fullyRequired');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->nullable)->toBeFalse()
        ->and($result->required)->toBeTrue();
});

it('sets both nullable and required correctly for nullable field', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyNullable');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->nullable)->toBeTrue()
        ->and($result->required)->toBeFalse();
});

it('sets nullable false but required false when field has default value', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyDefault');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->nullable)->toBeFalse()
        ->and($result->required)->toBeFalse();
});

it('sets nullable false but required false when field is optional', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first(fn($p) => $p->name === 'onlyOptional');

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result->nullable)->toBeFalse()
        ->and($result->required)->toBeFalse();
});

it('returns same context instance', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $result = $this->stage->process($context);

    expect($result)->toBe($context);
});

it('only modifies required and nullable properties without affecting others', function () {
    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->description = 'Test description';
    $context->example = 'example value';

    $result = $this->stage->process($context);

    expect($result->type)->toBe('string')
        ->and($result->description)->toBe('Test description')
        ->and($result->example)->toBe('example value')
        ->and($result->required)->not->toBeNull()
        ->and($result->nullable)->not->toBeNull();
});

it('falls back to the type-derived computation when the resolver cannot reconcile', function (
    string $property,
    bool $required,
    bool $nullable,
    bool $onlyValidatedWhenPresent,
) {
    // An empty inferrer list is the resolver's "cannot reconcile" signal, so this
    // is the only way to reach applyTypeDerivedFallback under tests/TestCase.
    $stage = new RequiredStage(new RequirementResolver([]));

    $dataClass = $this->dataConfig->getDataClass(RequiredTestData::class);
    $dataProperty = $dataClass->properties->first(fn($p) => $p->name === $property);

    $result = $stage->process(new ParameterContext($dataProperty->name, $dataProperty));

    expect($result->required)->toBe($required)
        ->and($result->nullable)->toBe($nullable)
        ->and($result->onlyValidatedWhenPresent)->toBe($onlyValidatedWhenPresent);
})->with([
    // [property, required, nullable, onlyValidatedWhenPresent]
    'plain type'                    => ['fullyRequired', true, false, false],
    'nullable type'                 => ['onlyNullable', false, true, false],
    'Optional type'                 => ['onlyOptional', false, false, true],
    'has default'                   => ['onlyDefault', false, false, false],
    'nullable + default'            => ['nullableWithDefault', false, true, false],
    'nullable + Optional'           => ['nullableAndOptional', false, true, true],
    'nullable + Optional + default' => ['allThree', false, true, true],
]);

class RequiredTestData extends Data
{
    public function __construct(
        public string $fullyRequired,
        public ?string $onlyNullable,
        public string|Optional $onlyOptional,
        public string|Optional|null $nullableAndOptional,
        public string $onlyDefault = 'default',
        public ?string $nullableWithDefault = null,
        public string|Optional $optionalWithDefault = 'default',
        public string|Optional|null $allThree = 'default',
    ) {}
}
