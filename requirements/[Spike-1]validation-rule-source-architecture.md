# [SPIKE-001] Where should validation documentation read its rules from?

**Type**: Architectural spike — decision required before STORY-001-001 through STORY-001-008 are committed to
**Raised**: 2026-09-22
**Blocks**: the whole `requirements/[Analysis]laravel-data-validation-attribute-documentation.md` decomposition (25 days)

## The question

The eight stories in this module all assume one approach: the package reads each Laravel Data validation attribute itself and registers a dedicated processor for it, family by family, extending the existing `AttributeProcessorRegistry`.

There is a second approach that was not considered when those stories were written. Laravel Data already resolves a Data class into a complete Laravel validation rule set, merging type-derived and attribute-derived rules under its own precedence. If the documentation generator consumed that resolved rule set, it would receive every uncovered attribute in a single pass rather than one family at a time.

**Decide which source of truth the documentation generator should read from, and re-shape the decomposition around the answer.**

## Evidence gathered so far

Verified by reading source; no code was run.

**The current requirement status is attribute-blind.**
`Abrha\LaravelDataDocs\Pipeline\Stages\RequiredStage` derives `required` and `nullable` solely from `$property->type->isNullable`, `$property->type->isOptional`, and `$property->hasDefaultValue`.

Those values come from `Spatie\LaravelData\Support\Factories\DataTypeFactory::buildProperty()`, where:
- `isNullable` is `$reflectionType?->allowsNull()` — pure PHP reflection (line 58)
- `isOptional` is true only when `Optional::class` appears in the type union (line 459)

`buildProperty()` does receive a `DataAttributesCollection`, but the only attribute it consumes anywhere is `#[DataCollectionOf]` at line 321, which affects iterable item typing. No validation attribute influences `DataPropertyType`.

`AttributeProcessorRegistry` registers no processor for `Required`, `Nullable`, `Sometimes`, `Present`, or `Filled`.

**Laravel Data already performs the reconciliation.**
`config/data.php` wires five rule inferrers by default, run by `DataValidationRulesResolver`:

```php
'rule_inferrers' => [
    SometimesRuleInferrer::class,
    NullableRuleInferrer::class,
    RequiredRuleInferrer::class,
    BuiltInTypesRuleInferrer::class,
    AttributesRuleInferrer::class,
],
```

`AttributesRuleInferrer` covers every validation attribute in the package. The first three cover exactly the attribute-versus-type precedence that STORY-001-001 currently asks the team to invent.

## What the analysis must answer

1. **Fidelity**: a resolved rule set is flat and stringly-typed (`required_if:type,business`). The current registry emits structured OpenAPI output — `format`, `pattern`, `enum`, binary upload types. Can the same structured output be recovered from resolved rules, or is fidelity lost for the families in STORY-001-004, STORY-001-006, and STORY-001-007?
2. **Extensibility**: the package's public `AttributeProcessor` extension point is documented in the README and is a supported customer-facing API. What happens to third-party registered processors under a rule-set-driven approach?
3. **Precedence ownership**: if the generator keeps reading attributes itself, it owns a precedence rule that can drift from the one Laravel Data enforces at runtime, and the documentation would then contradict the API. How is that avoided?
4. **Cost and blast radius**: the per-family approach touches `src/AttributeProcessing/**` only (one canvas). A rule-set approach touches `src/Pipeline/**` and likely `src/Services/**` as well. Which canvases change, and is the migration incremental or a single cutover?
5. **Decomposition shape**: does the answer collapse the eight per-family stories into a smaller set, keep them as-is, or produce a hybrid — rule set for requirement and conditional semantics, registry for structured schema output?

## Constraints that hold either way

- The package documents behaviour and must never change how requests are validated at runtime.
- Documentation generation must not require a database connection.
- Generated output must remain valid OpenAPI for existing consumers.
- Documentation may be published externally, so internal storage identifiers must not leak.

## Status of the eight stories

Provisional. Their acceptance criteria describe the required *documentation output* and remain valid regardless of the source of truth; their scope boundaries and the 25-day estimate assume the per-family approach and will be revised once this spike resolves.
