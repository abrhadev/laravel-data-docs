<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\AcceptedProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\Base\AcceptanceProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\ValueObjects\EnumInfo;
use Abrha\LaravelDataDocs\ValueObjects\EnumType;
use Spatie\LaravelData\Attributes\Validation\Accepted;

beforeEach(function () {
    $this->processor = new AcceptedProcessor();
});

it('lists the values Laravel compares against', function () {
    $context = conditionContext();

    $this->processor->process(new Accepted(), $context);

    expect($context->descriptions)->toBe(['Must be sent as one of <code>yes</code>, <code>on</code>, <code>1</code>, <code>"1"</code>, <code>true</code>, or <code>"true"</code>.']);
});

it('sets an example from the value set by type', function (?string $type, mixed $example) {
    $context = conditionContext();
    $context->type = $type;

    $this->processor->process(new Accepted(), $context);

    expect($context->example)->toBe($example);
})->with([
    'boolean' => ['boolean', true],
    'integer' => ['integer', 1],
    'number'  => ['number', 1],
    'string'  => ['string', 'yes'],
    'object'  => ['object', null],
]);

it('leaves an existing example untouched', function () {
    $context = conditionContext();
    $context->type = 'boolean';
    $context->example = 'given';

    $this->processor->process(new Accepted(), $context);

    expect($context->example)->toBe('given');
});

it('leaves an enum property to example generation', function () {
    $context = conditionContext();
    $context->type = 'string';
    $context->enumInfo = new EnumInfo(EnumType::STRING_BACKED, ConditionAccountType::cases());

    $this->processor->process(new Accepted(), $context);

    expect($context->example)->toBeNull();
});

it('writes nothing but a description', function () {
    $context = conditionContext();

    $this->processor->process(new Accepted(), $context);

    expect($context->required)->toBeNull()
        ->and($context->nullable)->toBeNull()
        ->and($context->format)->toBeNull()
        ->and($context->pattern)->toBeNull()
        ->and($context->minLength)->toBeNull();
});

it('renders a value list of any length', function (array $values, string $rendered) {
    expect((new AcceptanceProcessorTestList())->render($values))->toBe($rendered);
})->with([
    'none' => [[], ''],
    'one'  => [['yes'], '<code>yes</code>'],
    'two'  => [['yes', '1'], '<code>yes</code>, or <code>"1"</code>'],
]);

class AcceptanceProcessorTestList extends AcceptanceProcessor
{
    public function process(object $attribute, ParameterContext $context): void {}

    public function render(array $values): string
    {
        return $this->valueList($values);
    }
}
