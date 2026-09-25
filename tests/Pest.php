<?php

use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;
use Abrha\LaravelDataDocs\Tests\TestCase;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\DataConfig;

uses(TestCase::class)->in(__DIR__);

/**
 * Shared by the six conditional-requirement processor tests. `plain` carries no
 * attributes; `suppressed` carries #[Present], which strips every requiring rule
 * upstream and must therefore suppress any condition sentence.
 */
function conditionContext(string $property = 'plain'): ParameterContext
{
    $dataProperty = app(DataConfig::class)
        ->getDataClass(ConditionProcessorTestData::class)
        ->properties
        ->first(fn($candidate) => $candidate->name === $property);

    return new ParameterContext($dataProperty->name, $dataProperty);
}

class ConditionProcessorTestData extends Data
{
    public function __construct(
        public ?string $plain,
        #[Present]
        public ?string $suppressed,
    ) {}
}

enum ConditionAccountType: string
{
    case Business = 'business';
    case Charity = 'charity';
}
