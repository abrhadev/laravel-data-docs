<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Pipeline\Stages\ExampleGenerationStage;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

beforeEach(function () {
    $faker = \Faker\Factory::create();
    $faker->seed(1234);
    $this->stage = new ExampleGenerationStage($faker);
    $this->dataConfig = app(DataConfig::class);
});

it('does not override manually set example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->example = 'manual example';

    $result = $this->stage->process($context);

    expect($result->example)->toBe('manual example');
});

it('generates a numeric string within the bounds for a numeric string field', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->numericString = true;
        $context->exclusiveMinimum = 10;
        $context->maximum = 15;
        $context->pattern = '^[a-z]+$';

        $example = $this->stage->process($context)->example;

        expect($example)->toBeString()->toBeNumeric()
            ->and((int) $example)->toBeGreaterThan(10)->toBeLessThanOrEqual(15);
    }
});

it('pads a numeric string to its minimum length when the range cannot reach it', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->numericString = true;
        $context->exclusiveMaximum = 50;
        $context->minLength = 4;

        $example = $this->stage->process($context)->example;

        expect(strlen($example))->toBe(4)
            ->and($example)->toStartWith('0')
            ->and((int) $example)->toBeLessThan(50);
    }
});

it('caps the digits of a numeric string at its maximum length', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->numericString = true;
        $context->exclusiveMinimum = 90;
        $context->maxLength = 2;

        $example = $this->stage->process($context)->example;

        expect(strlen($example))->toBeLessThanOrEqual(2)
            ->and((int) $example)->toBeGreaterThan(90);
    }
});

it('rounds a fractional range inwards for a number example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestFloatData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'number';
        $context->minimum = 0.125;
        $context->maximum = 0.135;

        expect($this->stage->process($context)->example)->toBe(0.13);
    }
});

it('starts an integer example at the upper bound when that is below 1', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->exclusiveMaximum = 0.5;

    expect($this->stage->process($context)->example)->toBe(0);
});

it('clamps a bound beyond the int range for an integer example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 1e20;

    expect($this->stage->process($context)->example)->toBeInt();
});

it('pads a generated word up to the minimum length', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 30) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->minLength = 8;

        expect(strlen($this->stage->process($context)->example))->toBeGreaterThanOrEqual(8);
    }
});

it('skips object types', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'object';

    $result = $this->stage->process($context);

    expect($result->example)->toBeNull();
});

it('skips object array types', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'object[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeNull();
});

it('generates example from enum values', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE]
    );

    // Faker's pick depends on the global RNG and its own algorithm, so assert the
    // contract, not one value: the example is a case value, stable for a seed.
    $examples = [];

    foreach ([1, 2] as $run) {
        $faker = \Faker\Factory::create();
        $faker->seed(1234);
        $context->example = null;
        $examples[] = (new ExampleGenerationStage($faker))->process($context)->example;
    }

    expect($examples[0])->toBeIn(['active', 'inactive'])
        ->and($examples[1])->toBe($examples[0]);
});

it('generates array of unique enum values for enum array type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE, ExampleTestEnum::PENDING]
    );

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and(count($result->example))->toBeGreaterThan(0)
        ->and(count($result->example))->toBeLessThanOrEqual(3)
        ->and($result->example)->each->toBeIn(['active', 'inactive', 'pending'])
        ->and($result->example)->toBe(array_unique($result->example));
});

it('respects minItems constraint for enum arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->minItems = 2;
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE, ExampleTestEnum::PENDING]
    );

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeGreaterThanOrEqual(2);
});

it('respects maxItems constraint for enum arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->maxItems = 1;
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE, ExampleTestEnum::PENDING]
    );

    $result = $this->stage->process($context);

    expect(count($result->example))->toBe(1);
});

it('repeats enum values when the array needs more items than there are cases', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->minItems = 5;
    $context->maxItems = 10;
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: [ExampleTestEnum::ACTIVE, ExampleTestEnum::INACTIVE]
    );

    $result = $this->stage->process($context);

    // min:5 rejects fewer items, and the enum rule checks each item alone.
    expect($result->example)->toHaveCount(5)
        ->and(array_diff($result->example, ['active', 'inactive']))->toBe([]);
});

it('returns empty array for enum array with no values', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->enumInfo = new EnumInfo(
        enumType: EnumType::STRING_BACKED,
        cases: []
    );

    $result = $this->stage->process($context);

    expect($result->example)->toBe([]);
});

it('generates example for string type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and(strlen($result->example))->toBeGreaterThan(0);
});

it('generates email format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'email';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[^@]+@[^@]+\.[^@]+$/');
});

it('generates url format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'url';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^https?:\/\/.+/');
});

it('generates uuid format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'uuid';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

it('generates ipv4 format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'ipv4';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/');
});

it('generates ipv6 format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'ipv6';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[0-9a-f:]+$/i');
});

it('generates date format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'date';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{4}-\d{2}-\d{2}$/');
});

it('generates date-time format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'date-time';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
});

it('generates a date-time example with an RFC 3339 offset', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'date-time';

    expect($this->stage->process($context)->example)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/');
});

it('formats the example with the declared date format before the OpenAPI format', function (string $dateFormat, string $shape) {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'date';
    $context->dateFormat = $dateFormat;

    expect($this->stage->process($context)->example)->toMatch($shape);
})->with([
    'd/m/Y'    => ['d/m/Y', '#^\d{2}/\d{2}/\d{4}$#'],
    'H:i'      => ['H:i', '/^\d{2}:\d{2}$/'],
    'RFC 3339' => ['Y-m-d\TH:i:sP', '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/'],
]);

it('redraws a date that the declared format cannot read back', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $faker = Mockery::mock(\Faker\Generator::class);
    $faker->shouldReceive('dateTimeBetween')->twice()->andReturn(
        new DateTime('2024-02-29 10:00:00', new DateTimeZone('UTC')),
        new DateTime('2024-03-01 10:00:00', new DateTimeZone('UTC')),
    );

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->dateFormat = 'd/m';

    expect((new ExampleGenerationStage($faker))->process($context)->example)->toBe('01/03');
});

it('generates time format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'time';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^\d{2}:\d{2}:\d{2}$/');
});

it('generates password format example', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'password';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and(strlen($result->example))->toBeGreaterThanOrEqual(12)
        ->and(strlen($result->example))->toBeLessThanOrEqual(20);
});

it('generates an https uri example on a reserved example domain', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->format = 'uri';

        expect($this->stage->process($context)->example)
            ->toMatch('~^https://example\.(com|org|net)/[a-z]+(-[a-z]+)*$~');
    }
});

it('generates a json example that decodes', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->format = 'json';

        $example = $this->stage->process($context)->example;

        expect($example)->toBeString()
            ->and(json_decode($example, true, 512, JSON_THROW_ON_ERROR))->toBeArray()->toHaveCount(1);
    }
});

it('generates a password example holding every character class within the length bounds', function (?int $minLength, ?int $maxLength, int $low, int $high) {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 30) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->format = 'password';
        $context->minLength = $minLength;
        $context->maxLength = $maxLength;

        $example = $this->stage->process($context)->example;

        expect(strlen($example))->toBeGreaterThanOrEqual($low)->toBeLessThanOrEqual($high)
            ->and($example)->toMatch('/\p{Ll}/u')
            ->and($example)->toMatch('/\p{Lu}/u')
            ->and($example)->toMatch('/\pN/u')
            ->and($example)->toMatch('/\p{Z}|\p{S}|\p{P}/u');
    }
})->with([
    'no bounds'           => [null, null, 12, 20],
    'minimum below 12'    => [8, null, 12, 20],
    'minimum above 20'    => [32, null, 32, 32],
    'maximum below 12'    => [8, 10, 8, 10],
    'minimum and maximum' => [16, 64, 16, 20],
]);

it('generates example matching pattern', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->pattern = '[A-Z]{3}-\d{4}';

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/^[A-Z]{3}-\d{4}$/');
});

it('meets length constraints when a pattern is set', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->pattern = '[A-Z]{2}';
    $context->minLength = 10;
    $context->maxLength = 20;

    $result = $this->stage->process($context);

    // The pattern is unanchored, as the rule it stands for is.
    expect($result->example)->toBeString()
        ->and($result->example)->toMatch('/[A-Z]{2}/')
        ->and(strlen($result->example))->toBeGreaterThanOrEqual(10)->toBeLessThanOrEqual(20);
});

it('keeps a format example only when it matches the pattern', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'string';
        $context->format = 'email';
        $context->pattern = '^(a)';

        $example = $this->stage->process($context)->example;

        expect($example)->toMatch('/^a[^@]*@[^@]+\.[^@]+$/');
    }
});

it('meets the pattern when no format example matches it', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'email';
    $context->pattern = '[A-Z]{5}';

    $result = $this->stage->process($context);

    // The pattern is unanchored, as the rule it stands for is.
    expect($result->example)->toMatch('/[A-Z]{5}/');
});

it('respects minLength constraint', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->minLength = 20;

    $result = $this->stage->process($context);

    expect(strlen($result->example))->toBeGreaterThanOrEqual(20);
});

it('respects maxLength constraint', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->maxLength = 3;

    $result = $this->stage->process($context);

    expect(strlen($result->example))->toBeLessThanOrEqual(3);
});

it('generates example for integer type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';

    $result = $this->stage->process($context);

    expect($result->example)->toBeInt()
        ->and($result->example)->toBeGreaterThanOrEqual(1)
        ->and($result->example)->toBeLessThanOrEqual(100);
});

it('respects minimum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 100;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThanOrEqual(100);
});

it('respects maximum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->maximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeLessThanOrEqual(10);
});

it('respects both min and max constraints for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 5;
    $context->maximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThanOrEqual(5)
        ->and($result->example)->toBeLessThanOrEqual(10);
});

it('respects exclusiveMinimum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->exclusiveMinimum = 5;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThan(5);
});

it('respects exclusiveMaximum constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->exclusiveMaximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeLessThan(10);
});

it('respects multipleOf constraint for integers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->multipleOf = 5;

    $result = $this->stage->process($context);

    expect($result->example % 5)->toBe(0);
});

it('generates example for number type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestFloatData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number';

    $result = $this->stage->process($context);

    expect($result->example)->toBeFloat()
        ->and($result->example)->toBeGreaterThanOrEqual(1.0)
        ->and($result->example)->toBeLessThanOrEqual(100.0);
});

it('respects minimum constraint for numbers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestFloatData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number';
    $context->minimum = 100.5;

    $result = $this->stage->process($context);

    expect($result->example)->toBeGreaterThanOrEqual(100.5);
});

it('generates example for boolean type', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestBoolData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'boolean';

    $result = $this->stage->process($context);

    expect($result->example)->toBeBool();
});

it('generates example for string array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and(count($result->example))->toBeGreaterThan(0)
        ->and(count($result->example))->toBeLessThanOrEqual(3)
        ->and($result->example[0])->toBeString();
});

it('generates example for integer array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and($result->example[0])->toBeInt();
});

it('generates example for number array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and($result->example[0])->toBeFloat();
});

it('generates example for boolean array', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'boolean[]';

    $result = $this->stage->process($context);

    expect($result->example)->toBeArray()
        ->and($result->example[0])->toBeBool();
});

it('respects minItems constraint for arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->minItems = 2;

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeGreaterThanOrEqual(2);
});

it('respects maxItems constraint for arrays', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestArrayData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string[]';
    $context->maxItems = 1;

    $result = $this->stage->process($context);

    expect(count($result->example))->toBeLessThanOrEqual(1);
});

it('falls back to a basic string for unknown formats', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'hostname';
    $context->maxLength = 5;

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and(strlen($result->example))->toBeLessThanOrEqual(5);
});

it('clamps minLength to maxLength when they conflict', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->minLength = 8;
    $context->maxLength = 4;

    $result = $this->stage->process($context);

    expect($result->example)->toBeString()
        ->and(strlen($result->example))->toBe(4);
});

it('leaves example null for unsupported types', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestStringData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'SomeCustomClass';

    $result = $this->stage->process($context);

    expect($result->example)->toBeNull();
});

it('widens the range when minimum exceeds maximum', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestIntData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 50;
    $context->maximum = 10;

    $result = $this->stage->process($context);

    expect($result->example)->toBeInt()
        ->toBeGreaterThanOrEqual(50)
        ->toBeLessThanOrEqual(150);
});

it('respects both min and max constraints for numbers', function () {
    $dataClass = $this->dataConfig->getDataClass(ExampleTestFloatData::class);
    $property = $dataClass->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number';
    $context->minimum = 2;
    $context->maximum = 3;

    $result = $this->stage->process($context);

    expect($result->example)->toBeFloat()
        ->toBeGreaterThanOrEqual(2)
        ->toBeLessThanOrEqual(3);
});

it('draws the example from the allowed values', function (string $type, array $allowed) {
    $property = $this->dataConfig->getDataClass(ExampleTestStringData::class)->properties->first();

    foreach (range(1, 10) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = $type;
        $context->allowedValues = $allowed;
        $expected = $context->allowedValueList();

        $example = $this->stage->process($context)->example;

        foreach (is_array($example) ? $example : [$example] as $item) {
            expect(in_array($item, $expected, true))->toBeTrue();
        }
    }
})->with([
    'string'  => ['string', ['a', 'b']],
    'integer' => ['integer', ['1', '2']],
    'boolean' => ['boolean', ['1']],
    'items'   => ['string[]', ['a', 'b']],
]);

it('redraws an example the excluded values reject', function () {
    $property = $this->dataConfig->getDataClass(ExampleTestStringData::class)->properties->first();

    foreach (range(1, 20) as $run) {
        $context = new ParameterContext($property->name, $property);
        $context->type = 'integer';
        $context->minimum = 1;
        $context->maximum = 3;
        $context->excludedValues = ['1', '2'];

        expect($this->stage->process($context)->example)->toBe(3);
    }
});

it('generates a uri example with the recorded scheme, and without a path under a length bound', function () {
    $property = $this->dataConfig->getDataClass(ExampleTestStringData::class)->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'string';
    $context->format = 'uri';
    $context->uriScheme = 'ftp';

    expect($this->stage->process($context)->example)->toStartWith('ftp://example.');

    $bounded = new ParameterContext($property->name, $property);
    $bounded->type = 'string';
    $bounded->format = 'uri';
    $bounded->maxLength = 30;

    expect($this->stage->process($bounded)->example)->toMatch('#^https://example\.(com|org|net)/$#');
});

class ExampleTestStringData extends Data
{
    public function __construct(
        public string $field,
    ) {}
}

class ExampleTestIntData extends Data
{
    public function __construct(
        public int $number,
    ) {}
}

class ExampleTestFloatData extends Data
{
    public function __construct(
        public float $amount,
    ) {}
}

class ExampleTestBoolData extends Data
{
    public function __construct(
        public bool $flag,
    ) {}
}

class ExampleTestArrayData extends Data
{
    public function __construct(
        public array $items,
    ) {}
}

enum ExampleTestEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
}

it('keeps an integer example for a bound beyond the int range positive', function () {
    $property = $this->dataConfig->getDataClass(ExampleTestStringData::class)->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'integer';
    $context->minimum = 1e19;

    expect($this->stage->process($context)->example)->toBe(PHP_INT_MAX);
});

it('keeps a multipleOf example beyond the int range from wrapping to a negative number', function () {
    $property = $this->dataConfig->getDataClass(ExampleTestStringData::class)->properties->first();

    $context = new ParameterContext($property->name, $property);
    $context->type = 'number';
    $context->multipleOf = 7;
    $context->exclusiveMinimum = 1e19;

    expect($this->stage->process($context)->example)->toBeGreaterThan(0);
});
