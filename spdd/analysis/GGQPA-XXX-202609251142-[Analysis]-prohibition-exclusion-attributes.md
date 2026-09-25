# SPDD Analysis: Prohibition and Exclusion Attribute Documentation

## Original Business Requirement

### Referenced document: `requirements/[User-story-2]prohibition-and-exclusion-validation-attributes.md`

# [STORY-001-002] Prohibition and Exclusion Attribute Documentation

**Estimated effort**: 4 days

### Background

Some API fields are not merely optional — they are forbidden under particular circumstances, or they are silently dropped from the payload rather than rejected. Laravel Data expresses the first case through the prohibition family (`#[Prohibited]`, `#[ProhibitedIf]`, `#[ProhibitedUnless]`, `#[Prohibits]`) and the second through the exclusion family (`#[Exclude]`, `#[ExcludeIf]`, `#[ExcludeUnless]`, `#[ExcludeWith]`, `#[ExcludeWithout]`).

Neither family is reflected in the generated documentation today. The consequences differ but are both damaging. A prohibited field looks like an ordinary optional field, so a consumer sends it and receives a rejection they cannot explain from the documentation. An excluded field looks like an ordinary accepted field, so a consumer sends it, receives a success response, and never learns that the value was discarded — a far more expensive failure, because it surfaces as missing data weeks later.

Key points:

- **Business value and user needs**: Consumers need to know not only what they must send but what they must not send, and which fields will be ignored.
- **Relationship with other features**: This is the mirror image of the conditional requirement family covered by STORY-001-001. The two stories are independent and may ship in either order.
- **Why this capability is needed now**: Silently discarded input is the highest-severity documentation gap in the uncovered attribute set, because it produces data loss that neither side detects at the time of the request.

### Business Value

- Provide **API consumers** with an explicit statement of which fields are rejected, and under what condition, so that forbidden combinations are avoided before a request is sent.
- Support **safe migration of deprecated fields**, where a field is being phased out and is now prohibited for newer account types, by making the phase-out visible in the published contract.
- Enable **integration and data teams** to see which submitted fields are discarded rather than stored, eliminating a class of silent data-loss incidents.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Output must remain valid for both.
- **Business constraints**: The package documents behaviour and must not change it. A field that is accepted, rejected, or discarded today must behave identically after this story ships.

### Scope In

- Documenting the prohibition attributes `#[Prohibited]`, `#[ProhibitedIf]`, and `#[ProhibitedUnless]` as a statement of when the field must be absent.
- Documenting `#[Prohibits]`, which is stated from the inverse direction — sending this field forbids one or more *other* fields.
- Documenting the exclusion attributes `#[Exclude]`, `#[ExcludeIf]`, `#[ExcludeUnless]`, `#[ExcludeWith]`, and `#[ExcludeWithout]` as a statement that the field is ignored rather than rejected.

### Scope Out

- Conditional requirement attributes (`#[RequiredIf]` and relatives) — covered by STORY-001-001.
- Cross-field equality and comparison attributes — covered by STORY-001-003.
- Any change to runtime validation behaviour, including whether an excluded field is actually dropped.
- Removing excluded fields from the published contract entirely — an excluded field remains documented, with its behaviour described.
- Translating generated sentences into languages other than English.

### Acceptance Criteria

#### AC1: Unconditionally prohibited field is documented as forbidden

**Given** an endpoint accepts a Data object with a property `internal_ref` annotated `#[Prohibited]`
**When** documentation is generated for that endpoint
**Then** `internal_ref` is listed as optional
**And** its description states that the field must not be sent

> **Narrowed at sign-off (2026-09-25).** "Listed as optional" holds for a nullable or defaulted `internal_ref`. A non-nullable, undefaulted `#[Prohibited] string $internal_ref` is required at runtime, so it is published as required and adds "Note: this field is both required and prohibited, so no request can pass validation." Recorded under Approach in the pipeline canvas (`spdd/prompt/GGQPA-XXX-202608281600-[Codify]-pipeline-parameter-metadata.md`).

#### AC2: Conditionally prohibited field states its condition

**Given** a property `trial_ends_at` annotated `#[ProhibitedIf('plan', 'enterprise')]`
**When** documentation is generated
**Then** the description of `trial_ends_at` states that it must not be sent when `plan` is `enterprise`

#### AC3: Inverted conditional prohibition states its condition

**Given** a property `override_reason` annotated `#[ProhibitedUnless('role', 'admin')]`
**When** documentation is generated
**Then** the description of `override_reason` states that it must not be sent unless `role` is `admin`

#### AC4: A field that forbids other fields is documented from the consumer's perspective

**Given** a property `full_refund` annotated `#[Prohibits(['partial_amount', 'refund_items'])]`
**When** documentation is generated
**Then** the description of `full_refund` states that sending it forbids sending `partial_amount` and `refund_items`

#### AC5: Unconditionally excluded field is documented as ignored

**Given** a property `legacy_token` annotated `#[Exclude]`
**When** documentation is generated
**Then** `legacy_token` remains listed among the endpoint's parameters
**And** its description states that any value sent for this field is ignored

> **Narrowed at sign-off (2026-09-25), also for AC6 and AC7.** "Ignored" is worded at validator level, "Not validated, and removed from the validated input", because Laravel Data still passes an excluded value to the Data object. Recorded under Approach in the attribute-processors canvas (`spdd/prompt/GGQPA-XXX-202608281600-[Codify]-service-attribute-processors.md`).

#### AC6: Conditionally excluded field states its condition

**Given** a property `promo_code` annotated `#[ExcludeIf('order_type', 'internal')]`
**And** a property `manual_price` annotated `#[ExcludeUnless('pricing_mode', 'manual')]`
**When** documentation is generated
**Then** the description of `promo_code` states that it is ignored when `order_type` is `internal`
**And** the description of `manual_price` states that it is ignored unless `pricing_mode` is `manual`

#### AC7: Exclusion conditional on another field's presence is documented

**Given** a property `estimated_total` annotated `#[ExcludeWith('confirmed_total')]`
**And** a property `default_currency` annotated `#[ExcludeWithout('country')]`
**When** documentation is generated
**Then** the description of `estimated_total` states that it is ignored when `confirmed_total` is present
**And** the description of `default_currency` states that it is ignored when `country` is not present

#### AC8: Rejection and discarding are worded distinguishably

**Given** one property annotated `#[ProhibitedIf('plan', 'enterprise')]` and another annotated `#[ExcludeIf('plan', 'enterprise')]`
**When** documentation is generated
**Then** the two descriptions differ in wording such that a reader can tell that the first field causes the request to be rejected while the second is silently ignored

#### AC9: Prohibition combined with a requirement attribute produces one coherent description

**Given** a property `discount_code` annotated with both `#[ProhibitedIf('order_type', 'internal')]` and `#[Nullable]`
**When** documentation is generated
**Then** the description contains both the prohibition sentence and the null-value sentence
**And** the sentences appear in a stable, repeatable order across successive documentation builds

#### AC10: A malformed or unrecognised attribute does not break the build

**Given** a property carries a prohibition or exclusion attribute that references a field not present on the Data object
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- The wording for excluded fields must be unambiguous enough that a reader cannot mistake "ignored" for "rejected" — this distinction is the core value of the story.
- Repeated documentation builds over an unchanged codebase produce identical output.

## Domain Concept Identification

### Existing Concepts (from codebase)

- **Attribute processor registry** (`src/AttributeProcessing/AttributeProcessorRegistry.php`): a singleton map from an attribute's *exact* class to one processor. `AttributeProcessingStage` walks the property's package attributes, then its validation attributes in declaration order, and calls the processor for each attribute that has one. Any attribute with no entry is silently skipped. That is why all nine attributes in this story produce nothing today (verified, Result 1). Related to: every processor below.

- **Field-reference rendering convention** (`Processors/Base/FieldReferenceProcessor.php`): the house rule that a *field name* is rendered bold-italic and a *value* is rendered monospaced, so that "when **plan** is `enterprise`" is readable without parsing grammar. Every condition sentence in this story names at least one other field, so all of them inherit this convention. Related to: condition rendering.

- **Condition rendering and malformed-declaration safety** (`Processors/Base/RequirementConditionProcessor.php`, shipped by STORY-001-001): three capabilities this story needs almost unchanged:
  - Guarded parameter extraction, so an attribute whose internal state is uninitialised does not throw.
  - Value rendering: enums by backing value, `null` as a literal, several values as "one of: …".
  - A degraded wording when a compared value is an `ExternalReference` that is only known at request time.

  The same base also holds a fourth capability that this story must **not** inherit: *suppression*. A requirement sentence is dropped when a later requiring attribute or `#[Present]` displaces it. That rule mirrors how Laravel Data lets `RequiringRule`s replace one another. Prohibition and exclusion attributes are not `RequiringRule`s and are never displaced, so the suppression logic does not apply to them. Related to: prohibition statement, exclusion statement.

- **Conditional requirement processors** (`RequiredIf`, `RequiredUnless`, `RequiredWith`, `RequiredWithAll`, `RequiredWithout`, `RequiredWithoutAll`): the mirror-image family this story names as its sibling. Each is a small class that turns one upstream attribute into one sentence, with a degraded variant for an opaque condition. They set the house pattern of one class per attribute, fixed sentence templates, and an early return on a malformed declaration. Related to: new prohibition and exclusion processors.

- **Description assembly and ordering** (`AttributeProcessingStage`, then `RequirementDescriptionStage`): processor sentences are joined in attribute declaration order. The later stage then appends the requirement sentences ("Must be included…", "Only validated when…", "A null value is accepted."). Declaration order is source order and repeats across builds (STORY-001-001, Result 5). This already gives AC9 its stable "prohibition sentence, then null-value sentence" ordering. Related to: description, AC9.

- **Requirement status and its sole authority** (`Pipeline/Support/RequirementResolver.php`, `Pipeline/Stages/RequiredStage.php`): the only component allowed to decide `required` and `nullable`. It derives them from Laravel Data's own rule inferrers, so the published contract cannot drift from runtime. Laravel Data's `RequiredRuleInferrer` adds `Required` to every property that is not nullable, not `Optional`, and has no `Nullable` or `RequiringRule`. Neither family in this story implements `RequiringRule`. So a prohibited or excluded property with a non-nullable type and no default is **required at runtime**, and the resolver correctly publishes it as required (verified, Result 2). Related to: AC1, AC5, key business rules.

- **Hidden attribute** (`src/Attributes/Hidden.php`, `HiddenStage`): the package's own way to drop a field from the published contract. It is the concept that Scope Out keeps separate from `#[Exclude]`: a hidden field is omitted, an excluded field stays documented. Related to: exclusion statement.

- **Upstream prohibition family** (`Spatie\LaravelData\Attributes\Validation\Prohibited`, `ProhibitedIf`, `ProhibitedUnless`, `Prohibits`; laravel-data 4.23.0):
  - `ProhibitedIf` and `ProhibitedUnless` have the same shape as `RequiredIf`/`RequiredUnless`: one field plus a flattened value list.
  - `Prohibits` has the same shape as `RequiredWith`, including the uninitialised field list when it is declared with no fields.
  - `Prohibited` takes no condition, but can optionally wrap an Illuminate rule object whose condition is opaque.

  At runtime, "prohibited" means *absent or empty*. Laravel implements it as "not `required`", so `''`, `null` and `[]` pass (verified, Result 5). Related to: prohibition statement.

- **Upstream exclusion family** (`Exclude`, `ExcludeIf`, `ExcludeUnless`, `ExcludeWith`, `ExcludeWithout`):
  - Unlike their requirement and prohibition counterparts, `ExcludeIf` and `ExcludeUnless` compare against a **single** value, which may be a string, int, float, **bool**, enum or `ExternalReference`.
  - `ExcludeWith` and `ExcludeWithout` take exactly **one** field, not a list.
  - `Exclude` can wrap an opaque Illuminate rule object, like `Prohibited`.

  Laravel's validator applies exclusion rule by rule, in declaration order. Once a field is excluded, its remaining rules are skipped, and at the end it is removed from the *validated* array. A rule declared or inferred *before* the exclusion still runs, including an inferred `Required`. Related to: exclusion statement.

- **Laravel Data creation pipeline** (`Spatie\LaravelData\DataPipes\ValidatePropertiesDataPipe`) — *boundary context*: when a Data object is built from a request or via `validateAndCreate`, validation is run and its result is **discarded**. The object is built from the original payload. An excluded value therefore still reaches the Data object, **unvalidated** (verified, Result 3). It is dropped only for code that reads the validator's validated output, such as a `FormRequest`'s `validated()`. Related to: the "ignored" claim in AC5–AC7.

### New Concepts Required

- **Prohibition statement**: a description sentence saying that sending a non-empty value for this field causes the request to be *rejected*, and under which condition: always, when a field has a value, or unless a field has a value. It is the rejection counterpart of the requirement sentences from STORY-001-001, and uses the same condition-rendering conventions. It only adds to the description and never changes requirement status.

- **Inverse prohibition statement**: a sentence on the field that *carries* `#[Prohibits]`. It says that sending this field forbids sending the listed other fields. It is phrased from the consumer's point of view (AC4), not as a property of the other fields. It relates to the prohibition statement the way `RequiredWith` relates to `RequiredIf`: presence-triggered rather than value-triggered, and with the direction reversed.

- **Exclusion statement**: a description sentence saying that, under a condition, the field is *not validated and is removed from the validated input*. The condition is always, when a field has a value, unless a field has a value, when a field is present, or when a field is absent. The field stays documented (Scope Out). Per D3, it never claims the value is ignored or discarded by the application.

- **Rejection-versus-discard vocabulary**: a fixed, non-overlapping set of wording that separates the prohibition family from the exclusion family. Prohibition sentences use rejection language. Exclusion sentences never use "must not" or "rejected". This is the non-functional core of the story (AC8), so it is a named concept and not an incidental wording choice. It is the only place the two families' wording is allowed to differ in kind.

- **Unsatisfiable-declaration advisory** (D2): a sentence warning that no request can pass, because the field is both required and unconditionally prohibited. It depends on the resolved requirement status, so it is produced after requirement resolution and not by an attribute processor. It is aimed at the application developer as much as the consumer.

- **Neutral condition-rendering capability**: the value-rendering and guarded-extraction parts of the STORY-001-001 base, made available to the new processors *without* requirement suppression. It is new as a boundary, not as behaviour. It also needs one behaviour Story 1 did not: rendering a boolean compared value, which the current rendering turns into an empty string (verified, Result 6).

### Key Business Rules

- **Documentation only**: nothing in this story may change whether a field is accepted, rejected or discarded. Prohibition and exclusion processors add sentences. They must not write requirement status, type, format or constraints. *Governs: all new processors, `RequirementResolver`.*
- **Requirement status stays with the resolver**: whether a prohibited or excluded field is published as required is decided only by `RequirementResolver`, from what Laravel Data enforces. AC1 ("listed as optional") holds only when the property is nullable, `Optional`, or has a default. Otherwise the field really is required at runtime, and the contract must say so (see D2). *Governs: AC1, AC5.*
- **Rejection and discard must never be confused**: a reader must be able to tell from wording alone whether a request is rejected or a value is not processed (AC8, NFR 1). *Governs: rejection-versus-discard vocabulary.*
- **"Prohibited" means "absent or empty"** (implicit, surfaced here): Laravel accepts a prohibited key sent as `null`, `''` or `[]`. "Must not be sent" is slightly stricter than runtime, which is conservative and so safe for a consumer. However, `#[Prohibited, Nullable]` will read "must not be sent … a null value is accepted". That is true, but it reads as a contradiction unless the prohibition wording allows for it. *Governs: prohibition statement, AC1, AC9.*
- **Exclusion is order-sensitive at runtime** (implicit): rules declared or inferred before an exclusion attribute still run. An `#[Exclude]` on a non-nullable, no-default property still demands the key and fails "required" when it is absent (verified, Result 2). *Governs: exclusion statement, AC5.*
- **Exclusion discards at the validator, not at the Data object** (implicit, verified): an excluded value is removed from the validated array but still populates the Data object, unvalidated. *Governs: exclusion statement, D3.*
- **Prohibition is never displaced**: unlike requirement attributes, a prohibition or exclusion attribute stays in force whatever is declared after it. So its sentence is never suppressed by a later `#[Required]` or `#[Present]`. *Governs: new processors, and the choice of base capability.*
- **Deterministic output**: the same attributes in the same order produce byte-identical descriptions (NFR 2). There is no set iteration, hashing or locale-dependent formatting. *Governs: all new processors.*
- **A malformed declaration must never fail the build**: an unknown field, zero fields in `Prohibits`, or an opaque rule object produces a degraded sentence or no sentence, never an exception. Every other property is documented as normal (AC10). *Governs: all new processors.*
- **Condition wording follows the upstream shape**: `ExcludeIf` and `ExcludeUnless` state a single value and never "one of". `ExcludeWith` and `ExcludeWithout` state a single field and never "any of"/"all of". `ProhibitedIf` and `ProhibitedUnless` may state several values. *Governs: sentence templates.*

## Strategic Approach

### Solution Direction

- Add one attribute processor per upstream attribute (nine), registered in `AttributeProcessorRegistry` next to the STORY-001-001 requirement processors. This follows the house pattern: small final classes, fixed sentence templates, and a degraded template for opaque conditions.
- Data flow is unchanged: `AttributeProcessingStage` → processor appends one sentence to the context's description list → the existing join → `RequirementDescriptionStage` appends the null/present sentences → `Parameter` → Scribe → OpenAPI. No new stage, no new `Parameter` field, and no OpenAPI structural keywords, so both downstream outputs stay valid automatically (Integration points).
- Reuse STORY-001-001's condition rendering: field and value styling, multi-value rendering, enum and `null` handling, the `ExternalReference` degraded wording, and guarded parameter extraction. Keep requirement suppression out of reach of the new processors.
- `RequirementResolver` and `RequiredStage` are not changed. AC1's "optional" outcome comes from the property's declared type, as it does today (D2).
- Canvas ownership: all new code sits under `src/AttributeProcessing/**`, owned by `[Codify]-service-attribute-processors`. Per `spdd/prompt/INDEX.md`, that canvas must be updated and then regenerated. No cross-canvas contract changes, because `ParameterContext` already has the description list the processors write to.

### Key Design Decisions

- **D1 — Where the shared condition rendering lives**: the new processors need Story 1's rendering, but inheriting from `RequirementConditionProcessor` would also give them its suppression rule. That rule is wrong for them: a later `#[Required]` would hide a prohibition that is still enforced. The options are:
  - (a) Inherit and override suppression: least code, but every future reader has to know about the override.
  - (b) Move rendering and guarded extraction into a neutral shared base that both families extend, leaving suppression in the requirement base: a small refactor of Story 1 code, covered by its existing tests.
  - (c) Duplicate the rendering: no refactor, but two copies that will drift, including the boolean fix.

  → **Recommend (b).** Behaviour-preserving refactor, with Story 1's tests as the safety net.

- **D2 — Required status of prohibited and excluded fields**: AC1 expects `#[Prohibited]` fields to be "listed as optional". This is only true for a nullable, `Optional`, or defaulted property. `#[Prohibited] public string $internal_ref` with no default is required *and* prohibited at runtime: sending nothing fails "required", sending a value fails "prohibited". The options are:
  - (a) Keep the resolver as the sole authority and publish what runtime enforces: truthful, but such a field reads "Required … must not be sent".
  - (b) Teach the resolver that prohibition cancels requirement: satisfies AC1 literally, but publishes a field as optional that the API rejects when absent. That is exactly the failure STORY-001-000 was written to remove, and it breaks "documents behaviour, must not change it".

  - (c) As (a), plus an advisory sentence when the declaration can never pass.

  → **Decided (c): publish what runtime enforces, plus an advisory** (story owner, 2026-09-25). The resolver stays the sole authority, and AC1's *Given* is restated with a nullable or defaulted property. The advisory is raised only when the field is published as required **and** carries an unconditional `#[Prohibited]` with no wrapped rule object. That is the one combination no request can satisfy. A conditional prohibition can still be satisfied, and so can a required `#[Exclude]` field (send it, and it is dropped), so neither raises the advisory. Required status is only known after `RequiredStage`, so the advisory cannot come from the attribute processor. It must be emitted by a step that runs after requirement resolution, and it must follow the requirement sentences in a fixed order.

- **D3 — What "ignored" may honestly promise** (AC5–AC7 wording): the story assumes an excluded value is discarded. Verified on the request path and via `validateAndCreate`, Laravel Data builds the object from the raw payload. An excluded value still reaches application code, and **unvalidated**. Only code that reads validated output (`FormRequest::validated()`, `$validator->validated()`) sees it dropped. The package cannot tell which path the application uses. The options are:
  - (a) Say "ignored" as the story asks: publishes a claim that is false for the default Laravel Data path. This is a new silent-failure mode: consumers stop sending data the server actually uses.
  - (b) Say what the validator guarantees: the field is not validated and is removed from the validated input when the condition holds. Truthful and still clearly different from rejection (AC8), but a weaker promise to the consumer.
  - (c) Make the wording configurable per application.

  → **Decided (b): validator-level wording** (story owner, 2026-09-25). Example: *"Not validated, and removed from the validated input when **order_type** is `internal`."* No exclusion sentence may claim the value is "ignored", "discarded" or "not stored". (c) was rejected because it adds configuration surface this story does not need.

- **D4 — Vocabulary split between rejection and discard** (AC8, NFR 1):
  - Prohibition sentences use one rejection verb family that names the consequence (the request is rejected).
  - Exclusion sentences use one non-processing verb family and never "must" or "rejected".
  - Each family's condition templates are otherwise parallel, so that the verb is the only thing a reader has to compare.

  → Adopt as a canvas-level safeguard, backed by a test that places the two AC8 sentences side by side.

- **D5 — Prohibition wording and empty values**: runtime allows a prohibited key to be sent empty. The options are:
  - (a) "Must not be sent": simple and conservative. It conflicts on its face with the null-value sentence that `#[Nullable]` adds (AC9).
  - (b) "Must be omitted or empty": exact, but clumsy.

  → **Decided (a): "Must not be sent"** (story owner, 2026-09-25), with the prohibition sentence placed before the null-value sentence. Existing ordering already does this. Record the nuance as a known simplification in the canvas.

- **D6 — Opaque conditions**: some declarations cannot be rendered as a sentence:
  - `Prohibited` or `Exclude` wrapping an Illuminate rule object.
  - Compared values that are `ExternalReference`s.

  → Use Story 1's degraded-sentence approach: "depends on …" wording that names the field when known, and a generic conditional form when no field is known. Never state the unconditional form for a rule-wrapped `Prohibited`/`Exclude`, because it may be conditional.

- **D7 — `#[Prohibits]` and the forbidden fields**: AC4 documents only the carrier field. Adding a reciprocal sentence to `partial_amount` and `refund_items` would help readers, but it needs cross-property knowledge that the per-property pipeline does not have. → **Decided: carrier only** (story owner, 2026-09-25), as the AC says. The reciprocal sentence is a candidate follow-up.

### Alternatives Considered

- **Removing excluded fields from the contract (reuse `#[Hidden]` semantics)**: rejected by Scope Out. It would also hide fields that runtime *does* consume (D3).
- **Expressing prohibition structurally in OpenAPI (`not`, `readOnly`, vendor extensions)**: rejected. Conditional prohibition cannot be expressed per parameter, `Parameter` has no slot for it, and Scribe's human-readable pages would not show it. Description sentences reach both outputs.
- **Folding prohibition into `RequirementStatus`/`RequiredStage`**: rejected. That component answers "must I send this?", and making it also answer "may I send this?" breaks its single-authority contract (D2 (b)).
- **One data-driven processor with a template table for all nine attributes**: less code, but it breaks the one-class-per-attribute convention used by the registry, its tests and the owning canvas. The attribute shapes also differ enough (single value vs list, one field vs many, opaque rule object) that the table would need special cases.
- **Extending `RequirementConditionProcessor` directly**: rejected for the new families (D1). Its suppression rule is specific to requirements.

## Risk & Gap Analysis

### Requirement Ambiguities

- **"Ignored" vs actual runtime behaviour (D3)** — *resolved*: the story's premise that excluded input is "silently dropped" holds only at the validator. The story owner chose validator-level wording. AC5–AC7's "ignored" is read as that wording.
- **AC1 "listed as optional" (D2)** — *resolved*: AC1's *Given* is restated with a nullable or defaulted property. A required-by-type `#[Prohibited]` field is published as required, with the unsatisfiable-declaration advisory.
- **"Must not be sent" vs "absent or empty" (D5)** — *resolved*: "Must not be sent" is kept as an accepted simplification.
- **Excluded fields' other constraints**: an `#[Exclude, Email]` field publishes "Must be a valid email address", which is never enforced. For conditional exclusion it is enforced only sometimes. Should constraint sentences stay (as now) or be qualified? The story is silent. Recommended: leave unchanged, out of scope.
- **`#[Prohibits]` reciprocity (D7)** — *resolved*: carrier only. `partial_amount` and `refund_items` get no sentence.
- **Rule-object and `#[Rule('prohibited_if:…')]` declarations**: the story names attribute classes only. String rules declared through `#[Rule]` are not in scope, which matches Story 1. Confirm.

### Edge Cases

- **Non-nullable, no-default property with `#[Prohibited]` or `#[Exclude]`**: published as required, because runtime demands it. The field is unsatisfiable (`Prohibited`) or must be sent and is then dropped (`Exclude`). It is truthful, but it will surprise readers.
- **Boolean compared value** (`#[ExcludeIf('flag', false)]`, `#[ProhibitedIf('active', true)]`): the current value rendering turns `false` into an empty code span and `true` into `1` (Result 6). The shared renderer must print `true`/`false`. The same latent defect exists for Story 1 attributes given boolean values.
- **Multiple values in `ProhibitedIf`/`ProhibitedUnless`**: rendered as "one of: …", like the requirement family. `ExcludeIf` and `ExcludeUnless` can hold only one value.
- **Enum and `null` compared values**: enums render by backing value, and `null` renders as the literal `null` (Story 1 behaviour, reused).
- **`ExternalReference` compared value**: degraded sentence that names the field only.
- **`Prohibits` with zero fields**: upstream leaves its field list uninitialised and `parameters()` throws. The guarded extraction must produce no sentence (AC10).
- **`Prohibits` with one field vs several**: the grammar must cover both ("forbids sending **a**" vs "**a** and **b**", as AC4 expects).
- **Dangling field reference** (AC10): the referenced field is only rendered as text and never resolved, so it is harmless. That is already the case for Story 1 (its Result 6).
- **Nested Data objects**: a field reference is relative to its sibling scope, or to the root when `fromRoot`. The documented parameter name is the prefixed path (`order.promo_code`), but the condition names the field as declared. Readers may need the scope made clear. Story 1 has the same behaviour.
- **Excluded parent object**: excluding a nested Data property at runtime also removes its children (the validator matches by prefix), but the children's descriptions say nothing.
- **Both families on one property** (`#[ExcludeIf(...), ProhibitedIf(...)]`): which wins at runtime depends on order, and both sentences will be published in declaration order. This is rare, and acceptable if the order is stable.
- **`#[Hidden]` plus an exclusion attribute**: Hidden wins and the field is not documented. No conflict.
- **GET query parameters**: the same pipeline applies, so the sentences appear for query parameters too. No special handling is needed.

### Technical Risks

- **Publishing a false "ignored" claim (verified, D3)**: impact is high. It creates the same kind of silent data-contract mismatch the story is trying to remove, in the other direction. Mitigation: D3 is decided (validator-level wording). Pin it with a test, and add a canvas safeguard forbidding "ignored"/"discarded" in exclusion sentences.
- **Regression in Story 1 processors from the shared-base refactor (D1)**: impact is moderate; the refactor touches six shipped processors. Mitigation: behaviour-preserving move only, and Story 1's unit and integration tests must pass unchanged.
- **Accidental suppression**: if a new processor reaches Story 1's suppression rule, a later `#[Required]` or `#[Present]` silently hides a prohibition that is still enforced. Mitigation: a canvas safeguard plus a test declaring `#[ProhibitedIf(...), Required]`.
- **Exact-class registry lookup**: a subclass of an upstream attribute is not matched. This is an existing limitation shared with every processor, not new here, and is left unchanged.
- **HTML in descriptions**: the new sentences use the same `<code>` / `<b><i>` markup as Story 1, which is already accepted by both Scribe pages and OpenAPI descriptions. No new risk.
- **Upstream shape changes**: the new processors depend on the order of `parameters()` for nine upstream classes. Pinned by `spatie/laravel-data ^4.14` and verified on 4.23.0. Mitigation: per-attribute tests built from the real upstream classes, not stubs.
- **Canvas drift**: the `[Codify]-service-attribute-processors` canvas must describe the new processors and the neutral base, or later regeneration will drop them (INDEX rule). Mitigation: update it through `/spdd-prompt-update` first, not a lingering `[Feat]` canvas.

### Acceptance Criteria Coverage

| AC# | Description | Addressable? | Gaps/Notes |
|-----|-------------|--------------|------------|
| 1 | `#[Prohibited]` field listed as optional, described as must not be sent | Yes | With AC1's *Given* restated as a nullable or defaulted property (D2). A required-by-type property is published as required, plus the unsatisfiable-declaration advisory. |
| 2 | `#[ProhibitedIf('plan','enterprise')]` states its condition | Yes | Reuses Story 1 condition rendering. Multi-value, enum, `null` and `ExternalReference` handled the same way. |
| 3 | `#[ProhibitedUnless('role','admin')]` states its condition | Yes | Same as AC2 with "unless" wording. |
| 4 | `#[Prohibits([...])]` states the carrier forbids the others | Yes | Carrier only (D7). The zero-field declaration must be guarded (AC10). |
| 5 | `#[Exclude]` field stays listed, described as ignored | Yes | "Stays listed" is already true. "Ignored" is worded at validator level (D3). A required-by-type property is published as required, with no advisory, because it can be satisfied. |
| 6 | `#[ExcludeIf]` / `#[ExcludeUnless]` state their conditions | Yes | Single value, validator-level wording (D3). Boolean values need explicit rendering. |
| 7 | `#[ExcludeWith]` / `#[ExcludeWithout]` state presence conditions | Yes | Single field, validator-level wording (D3). |
| 8 | Rejection and discard worded distinguishably | Yes | Rejection-versus-discard vocabulary (D4). The validator-level exclusion wording never uses rejection verbs. |
| 9 | `#[ProhibitedIf]` + `#[Nullable]` → both sentences, stable order | Yes | Existing ordering already puts processor sentences before the null-value sentence, and declaration order is repeatable. D5 notes that the combined reading looks contradictory. |
| 10 | Malformed or unrecognised attribute does not break the build | Yes | Dangling references are harmless. Guarded extraction covers `Prohibits` with no fields. Opaque rule objects degrade (D6). |

## Empirical Verification

A throwaway probe was run in the project's `abrha/jig:8.4` image against laravel-data 4.23.0 on commit `bad226e`. No repository files were changed.

### Result 1 — neither family contributes anything today ✅
Every property carrying one of the nine attributes gets only its type, default and nullable sentences. For example, `#[ProhibitedIf('order_type','internal'), Nullable] ?string $discount_code` → "Must be a string. A null value is accepted." This confirms the gap the story describes.

### Result 2 — a non-nullable, no-default prohibited or excluded property is required at runtime ✅
`#[Prohibited] string $a` and `#[Exclude] string $b` infer the rules `required, string, prohibited` and `required, string, exclude`. Both are documented as `required=true`. Validating `[]` fails with "The a field is required." and "The b field is required.". Sending `b` passes. `a` cannot pass in any request.

### Result 3 — excluded values still reach the Data object ✅
`validateAndCreate` and `from(Request)` with `legacy_token` (`#[Exclude]`) and `promo` (`#[ExcludeIf('plan','enterprise')]`, `plan=enterprise`) both produce a Data object holding `"SENT"` and `"PROMO"`. The validated array for the same payload contains only `plan`. Exclusion therefore discards at the validator, not at the Data object.

### Result 4 — defaulted and nullable properties are published as optional ✅
`#[Prohibited] string $internal_ref = 'x'`, `#[Prohibited] ?string`, and `#[Exclude] ?string` are all `required=false`. AC1's and AC5's "optional" outcome therefore needs no change to requirement derivation for these shapes.

### Result 5 — "prohibited" accepts an empty value ✅
`#[Prohibited] ?string $prohibited_nullable` sent as `''` passes validation.

### Result 6 — boolean compared values lose their meaning ✅
`#[ExcludeIf('flag', false)]` exposes the value `false` through `parameters()`. The existing value rendering casts it to an empty string, so a sentence built with it today would print an empty code span.

### Correction this forces on the story
The story's premise that an excluded field "is silently dropped rather than rejected" is true only at the validator. For an application using Laravel Data's standard request-to-object path, the value is accepted *unvalidated*. The story owner resolved this on 2026-09-25: exclusion is worded at validator level (D3), AC1's *Given* uses a nullable or defaulted property, and an unsatisfiable `#[Prohibited]` declaration gets an advisory (D2).
