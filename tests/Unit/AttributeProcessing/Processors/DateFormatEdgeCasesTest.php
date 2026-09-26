<?php

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateFormatProcessor;
use Spatie\LaravelData\Attributes\Validation\DateFormat;

it('treats a format containing a null byte as one no value can match', function () {
    $context = conditionContext();
    $context->type = 'string';

    (new DateFormatProcessor())->process(new DateFormat("Y\0m"), $context);

    expect($context->format)->toBeNull()
        ->and($context->descriptions)->toHaveCount(1)
        ->and($context->descriptions[0])->toContain('which no value can match');
});
