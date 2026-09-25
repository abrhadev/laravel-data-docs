<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'ray'])
    ->each->not->toBeUsed();

// Spatie's validation internals carry no backwards-compatibility guarantee.
// RequirementResolver is the single repair site for an upstream change, which
// only holds while it is the only importer. Scoped to these four types rather
// than the whole namespace: ValidationRule and FieldReference live there too
// and are ordinary collaborators, read by AttributeProcessingStage and
// FieldReferenceProcessor respectively.
arch('validation internals are confined to the requirement resolver')
    ->expect([
        'Spatie\LaravelData\Support\Validation\PropertyRules',
        'Spatie\LaravelData\Support\Validation\RequiringRule',
        'Spatie\LaravelData\Support\Validation\ValidationContext',
        'Spatie\LaravelData\Support\Validation\ValidationPath',
    ])
    ->toOnlyBeUsedIn('Abrha\LaravelDataDocs\Pipeline\Support\RequirementResolver');
