# SPDD Analysis: Validation Rule Source Architecture

## Original Business Requirement

### Accompanying instruction

This is an architectural spike, not a feature story. The analysis must settle one decision: should the documentation generator read validation rules from Laravel Data's resolved rule set (DataValidationRulesResolver + the five configured rule inferrers) or continue reading validation attributes directly via AttributeProcessorRegistry — or a hybrid of the two.

Answer the five questions listed under "What the analysis must answer" in the spike brief, with particular weight on (1) whether structured OpenAPI output (enum sets, binary upload parameters, format/pattern) can be recovered from flat rule strings, and (2) what happens to the public AttributeProcessor extension point documented in the README.

Relevant existing code: src/Pipeline/Stages/RequiredStage.php, src/AttributeProcessing/AttributeProcessorRegistry.php, src/Services/**, src/Strategies/**, and the canvas ownership map in spdd/prompt/INDEX.md. The vendor package is at vendor/spatie/laravel-data.

Conclude with a recommended decomposition shape so the eight provisional stories in requirements/ can be revised.

### Referenced document: `requirements/[Spike-1]validation-rule-source-architecture.md`

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


---

## Domain Concept Identification

### Existing Concepts (from codebase)

- **Validation attribute** (`Spatie\LaravelData\Support\Validation\ValidationRule` and its subclasses): the unit of declared validation on a Data property. Critically, these are not inert markers — they are structured objects. `StringValidationAttribute` exposes `keyword()` and `parameters()`; `ObjectValidationAttribute` exposes `getRule()`. `Mimes` holds its extension array, `In` holds its value list. Related to: attribute processor, parameter context.

- **Attribute processor + registry** (`src/AttributeProcessing/`): the package's own translation layer from one attribute class to documentation output. A process-global singleton keyed by attribute class name, with three implementation styles (static, comparison/size base classes, standalone). Owns the package's public extension point. Related to: parameter context, pipeline.

- **Parameter context** (`src/Pipeline/Context/ParameterContext.php`): the mutable accumulator carried through the pipeline. Already carries structured OpenAPI fields — `format`, `pattern`, `minimum`/`maximum`, `minLength`/`maxLength`, `minItems`/`maxItems`, `multipleOf`, `enumInfo` — plus `required`, `nullable`, and an ordered `descriptions` array. Related to: parameter record.

- **Pipeline and stages** (`src/Pipeline/`): fixed-order transformation of a Data property into a parameter context. Related to: every stage, notably attribute processing and required.

- **Requirement derivation** (`src/Pipeline/Stages/RequiredStage.php`): currently sets `required` and `nullable` from the Spatie type model alone.

- **Parameter record** (`src/ValueObjects/Parameter.php`): the output record. Carries `enumValues` separately from `openApiAttributes`, which is emitted under a `custom.openAPI` key for the OpenAPI generator to merge.

- **Laravel Data rule inference** (`vendor/spatie/laravel-data/src/RuleInferrers/`): five inferrers, wired by default in `config/data.php`, that assemble a `PropertyRules` collection of `ValidationRule` objects for a property, reconciling type-derived and attribute-derived requirement semantics.

- **Laravel Data rule resolution** (`DataValidationRulesResolver`): the payload-driven orchestration that runs the inferrers across a whole Data class and denormalizes the result into Laravel rule strings.

### New Concepts Required

- **Requirement reconciliation**: a single, authoritative determination of a property's requirement status that accounts for both the declared type and any requirement attribute, replacing the type-only derivation. This is the only genuinely new concept the spike surfaces; everything else already exists.

- **Rule-family translation coverage**: not a new structural concept, but the recognition that the ~50 uncovered attributes are missing *registry entries*, not a missing source of truth.

### Key Business Rules

- **Documentation must not contradict runtime validation**: the requirement status the package publishes must match what Laravel Data actually enforces. This governs requirement reconciliation and is the rule that makes precedence ownership a real risk rather than a stylistic preference.

- **Generated output must remain valid OpenAPI**: governs parameter record and the OpenAPI attribute channel.

- **Documentation generation must be environment-independent**: no database connection, no request payload. This governs which Laravel Data facilities the package may safely consume, and is decisive for this spike.

- **The public extension point is a supported contract**: third parties register processors keyed by attribute class, as documented in the README. Governs attribute processor and registry.

---

## Strategic Approach

### Solution Direction

**The spike's framing contains a false premise, and correcting it resolves the decision.**

The brief presents two sources of truth: read attributes directly, or consume Laravel Data's resolved rule set. Exploration shows the package *already* reads from the same enumeration Laravel Data itself uses. `AttributeProcessingStage` iterates `$context->property->attributes->all(ValidationRule::class)` — the identical call `AttributesRuleInferrer` makes. Every one of the ~50 uncovered attributes is already being visited on every documentation build; the registry simply returns no processor for them, so each is silently skipped.

There is therefore no missing source of truth and no discovery problem to solve. The recommended direction is to **keep the registry as the translation layer and close the coverage gap family by family**, with one targeted exception described below.

Consuming the resolved rule set is rejected on two independent grounds, either of which is sufficient:

1. **It is a fidelity downgrade, not an upgrade.** The attributes are already the structured rule objects. `RuleDenormalizer` converts them *to* flat strings. Routing through it would mean discarding `Mimes::parameters()` and `In::getRule()` in order to re-parse `mimes:pdf,docx` back out of a string. The spike's question 1 inverts the actual direction of information loss.

2. **The resolver is payload-driven and unsafe without a request.** `DataValidationRulesResolver::execute()` requires `array $fullPayload`. A property that has a default value and is absent from the payload is skipped outright; branches on `isOptionalAndEmpty` and `isNullableAndEmpty` read payload values; and the concrete class for morphable Data objects is resolved *from* the payload. Documentation generation has no payload, so invoking it with `[]` would silently omit properties from the published contract — a worse defect than the one being fixed.

**The one thing worth taking from Laravel Data is the precedence logic, and it can be taken safely.** All five rule inferrers accept a `ValidationContext` and **none of them read it**. `RequiredRuleInferrer`, `NullableRuleInferrer`, `SometimesRuleInferrer`, `AttributesRuleInferrer`, and `BuiltInTypesRuleInferrer` each ignore the parameter entirely. The precedence logic is payload-independent even though the resolver wrapped around it is not, which means the package can obtain authoritative requirement semantics per property without going near the payload-driven path.

### Key Design Decisions

- **Source of truth for attribute discovery**: registry-driven (status quo) versus resolver-driven. Trade-off: the resolver promises breadth in one pass, but delivers flat strings, payload dependence, and silent property omission; the registry preserves structure and per-attribute control but requires one entry per attribute. → **Keep the registry.** The breadth advantage is illusory because discovery is already complete.

- **Source of truth for requirement status**: keep the type-only derivation, invent a precedence rule, or delegate to Laravel Data's payload-independent inferrer logic. Trade-off: inventing a rule is self-contained but creates a second precedence authority that can drift from runtime behaviour and violate the "must not contradict" business rule; delegating couples the package to inferrer internals but guarantees agreement. → **Delegate to the inferrer logic**, which is the single highest-value change in this whole module and is a prerequisite for the conditional-requirement family.

- **Stage ordering**: exploration found a concrete defect that no story currently covers. `PipelineFactory::createDefault` places `AttributeProcessingStage` *before* `RequiredStage`, and `RequiredStage` assigns `$context->required` and `$context->nullable` unconditionally. Any processor registered for `#[Required]`, `#[Nullable]`, or `#[Sometimes]` would have its output overwritten. → **Requirement reconciliation must be resolved inside the requirement stage, not by registering processors for those attributes.** This changes which canvas the work belongs to.

- **Extension point**: the public `AttributeProcessor` contract survives untouched under the recommended direction. Under a resolver-driven approach it would break — third-party registrations are keyed by attribute class (`register(InEnumCases::class, ...)` in the README), and that key has no meaning once rules are denormalized to strings. → This alone would be grounds to reject the resolver approach even if the fidelity and payload problems did not exist.

- **Decomposition shape**: collapse, keep, or hybrid. → **Keep the eight per-family stories and add one foundational story.** The per-family split maps one-to-one onto registry entries, which is the actual unit of work. The foundational story extracts requirement reconciliation out of STORY-001-001, where it currently sits mis-scoped and mis-canvassed.

### Alternatives Considered

- **Full resolver consumption** (`DataValidationRulesResolver` + `RuleDenormalizer`): rejected on fidelity loss, payload dependence causing silent property omission, and breakage of the public extension point.

- **Invent a package-local precedence rule** (as STORY-001-001 currently specifies): rejected because it creates a second authority on requirement semantics that can silently diverge from runtime enforcement, which is exactly the contradiction the module exists to eliminate.

- **Collapse the eight stories into three or four broader ones**: rejected because the per-family boundaries match the registry's unit of work and keep each story independently shippable; broader stories would not reduce total effort and would harm parallelism.

---

## Risk & Gap Analysis

### Requirement Ambiguities

- **The spike's premise that the two approaches are alternatives**: they are not symmetric options. Attribute discovery is already solved; only requirement precedence is genuinely open. The decomposition should be revised on that narrower basis.

- **"Delegate to the inferrer logic" admits two readings**: invoking the configured inferrers directly, or reimplementing their conditions in the package. Both satisfy the non-contradiction rule; they differ in coupling to Laravel Data internals versus duplication risk. This needs settling in the REASONS Canvas phase, not here.

- **Scope of reconciliation**: whether reconciliation applies only to the requirement family or should also govern type assertions, where `BuiltInTypesRuleInferrer` likewise derives rules from the type model. STORY-001-007 is affected and its boundary is currently unstated.

### Edge Cases

- **Properties with defaults**: the resolver skips them and the current stage marks them optional. If reconciliation delegates to inferrer logic, the interaction between a default value and an explicit `#[Required]` must be settled deliberately rather than inherited by accident.

- **Morphable Data objects**: the resolver selects the concrete class from payload. Any documentation-time reconciliation has no payload and must define what it publishes for a morphable property.

- **Nested and collection Data properties**: the resolver recurses into nested Data classes with a payload-derived path. Requirement reconciliation at documentation time has no equivalent recursion, so nested behaviour is undefined by every current story.

- **Third-party processors registered for an attribute the package now covers by default**: the registry's last-write-wins semantics mean load order decides the winner, and the outcome differs depending on whether the consumer registers before or after the service provider boots.

- **Repeatable attributes**: several validation attributes may appear more than once on a property. `all()` returns every instance, but the registry maps one processor per class, so the processor is invoked repeatedly against shared context fields — relevant to the description-ordering criteria in nearly every story.

### Technical Risks

- **Coupling to Laravel Data internals**: `RuleInferrer`, `PropertyRules`, and `RequiringRule` are support-namespace types with no stated backwards-compatibility guarantee. A minor upgrade could change them. Mitigation direction: isolate the dependency behind a single package-owned seam so an upstream change has one repair site, and pin behaviour with tests that fail loudly on upgrade.

- **Cross-canvas blast radius**: per `spdd/prompt/INDEX.md`, requirement reconciliation touches `src/Pipeline/**` (pipeline-parameter-metadata canvas) while the eight family stories touch `src/AttributeProcessing/**` (service-attribute-processors canvas). Several families additionally need new fields on `ParameterContext` and `Parameter`, reaching the documentation-records canvas. Mitigation direction: isolate the pipeline change in its own story so the family stories stay within a single canvas.

- **Singleton registry with no reset**: noted in the existing attribute-processors canvas. Adding ~50 default registrations enlarges a process-global object built once per process, which interacts badly with test isolation.

- **Structured output beyond existing context fields**: publishing allowed-value sets from `#[In]`, binary upload parameters from `#[File]`/`#[Image]`, and array-shape constraints requires fields `ParameterContext` does not yet carry. `enumInfo` is currently populated from the PHP enum type, not from a validation attribute, so STORY-001-004 must reconcile two sources into one published set.

- **Verified by execution** (see Empirical Verification below): the load-bearing claims were confirmed by running code under `./jig pest` on PHP 8.4, not by static reading alone. The residual risk is narrower than first stated: it is the absence of a permanent regression test pinning these behaviours, which the foundational story should add.

### Analysis Question Coverage

The spike brief poses five questions rather than acceptance criteria; each is assessed below.

| Q# | Question | Answered? | Conclusion / Gaps |
|-----|-------------|--------------|-------------|
| 1 | Fidelity: can structured OpenAPI output survive flat rule strings? | Yes | Question is inverted. Attributes are already structured objects carrying `parameters()`/`getRule()`; denormalization is where structure is lost. No fidelity argument favours the resolver. |
| 2 | Extensibility: what happens to third-party `AttributeProcessor` registrations? | Yes | They break under a resolver approach, since registration is keyed by attribute class. They are untouched under the recommendation. |
| 3 | Precedence ownership: how is drift from runtime behaviour avoided? | Yes | Delegate to the five rule inferrers, all of which ignore `ValidationContext` and are therefore payload-independent. Exact delegation mechanism deferred to REASONS Canvas. |
| 4 | Cost and blast radius: which canvases change, incremental or cutover? | Yes | Fully incremental; no cutover. Eight family stories stay in the attribute-processors canvas; one new foundational story touches the pipeline canvas; some families also touch documentation-records. |
| 5 | Decomposition shape | Yes | Keep the eight family stories; add one foundational story; shrink STORY-001-001. Detail below. |

### Recommended Decomposition Shape

- **Add STORY-001-000, "Requirement reconciliation foundation"** (~2 days, pipeline canvas): resolve `required`/`nullable`/`sometimes` from declared type *and* requirement attributes under Laravel Data's own precedence, and fix the stage-ordering defect that currently lets `RequiredStage` overwrite attribute-derived values. Prerequisite for STORY-001-001 only.

- **Shrink STORY-001-001 back to the conditional family** (4 days → ~2 days): `RequiredIf`, `RequiredUnless`, `RequiredWith(All)`, `RequiredWithout(All)`, `Present`, `Filled`. Its reconciliation scope and the invented precedence rule move to STORY-001-000, as does its current cross-canvas reach. ACs 1, 6, 11 and 12 relocate; the conditional ACs stay.

- **Keep STORY-001-002 through STORY-001-008 as written.** Their boundaries match registry entries and their acceptance criteria describe documentation output, which is unaffected by this decision. Estimates stand.

- **Flag STORY-001-004, STORY-001-006 and STORY-001-007** as also touching the documentation-records canvas, since each needs `ParameterContext` fields that do not yet exist. STORY-001-004 additionally must reconcile attribute-derived allowed values with the existing type-derived `enumInfo`.

- **Revised total**: nine stories, approximately 25 days — unchanged in aggregate, but with the foundational risk isolated into a single short story that can be validated before the remaining 23 days are committed.

---

## Empirical Verification

Run under `./jig pest` (PHP 8.4, Orchestra Testbench) against a sample Data class. The throwaway test was removed after running; the foundational story should add a permanent equivalent.

### Result 1 — the five rule inferrers are payload-independent ✅

All five were invoked with `new ValidationContext(null, null, ValidationPath::create())`. None threw; all produced correct rules. Running them outside the payload-driven resolver is safe.

### Result 2 — every uncovered attribute is already enumerable ✅

`$property->attributes->all(ValidationRule::class)` returned `RequiredIf`, `Prohibits`, `In`, `Mimes` and `Required` for the sample properties. These are the exact values `AttributeProcessingStage` already iterates. **There is no discovery gap.**

### Result 3 — structure is on the attribute objects, and the string form is lossy ✅

| Access path | Value |
| --- | --- |
| `Mimes::parameters()` | `[['pdf', 'docx']]` — a usable array |
| `In::getRule()` cast to string | `in:"pending","shipped","cancelled"` — quoted, would need re-parsing |

Confirms the fidelity argument runs *against* the resolver approach.

### Result 4 — the stage-ordering defect is real ✅

| Property | Package publishes today | Laravel Data actually enforces |
| --- | --- | --- |
| `#[Required] public ?string $email` | `required: false`, `nullable: true` | `nullable, string, required` |
| `#[Nullable] public string $middle_name` | `required: true`, `nullable: false` | `required, string, nullable` |
| `#[Sometimes] public string $coupon_code` | `required: true`, `nullable: false` | `required, string, sometimes` |

`email` is published as optional while the API requires it. This is a live documentation defect, reproducible today, that no existing story covers.

### Result 5 — the resolver silently drops defaulted properties ✅

`SpikeSampleData::getValidationRules([])` returned keys for seven of eight properties. `with_default` (a property carrying both a default value and `#[Required]`) was **absent entirely**. Confirms the resolver is unsafe for documentation generation, which has no payload.

### Correction this forces on STORY-001-001

Result 4 shows `#[Nullable]` on a non-nullable property yields `required, string, nullable` — the field stays **required** and merely accepts null. STORY-001-001's AC6 currently asserts only that the field "is documented as accepting a null value" and says nothing about its requirement status, which would let a wrong implementation pass. The same applies to `#[Sometimes]`, which yields `required, string, sometimes` rather than a plain optional.

This is the clearest argument for delegating rather than inventing: the real semantics are subtler than the hand-written ACs assumed, and inheriting them removes a whole class of specification error.
