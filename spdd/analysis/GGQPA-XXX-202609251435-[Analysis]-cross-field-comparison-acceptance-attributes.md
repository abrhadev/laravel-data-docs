# SPDD Analysis: Cross-Field Comparison and Acceptance Attribute Documentation (STORY-001-003)

## Original Business Requirement

# [STORY-001-003] Cross-Field Comparison and Acceptance Attribute Documentation

**Estimated effort**: 3 days

### Background

A number of validation rules constrain a field by reference to another field rather than to a fixed value: a password confirmation must match the password, a new email must differ from the old one, a selected option must appear in a submitted list. Laravel Data expresses these through `#[Same]`, `#[Different]`, `#[Confirmed]`, and `#[InArray]`. A closely related group — `#[Accepted]`, `#[AcceptedIf]`, `#[Declined]`, `#[DeclinedIf]` — constrains a field to a fixed set of truthy or falsy values, typically for consent and terms-of-service checkboxes.

None of these appear in the generated documentation. The `#[Confirmed]` case is the most visible failure: it requires the consumer to send a companion field (`password_confirmation`) that does not exist as a property on the Data object at all, so the published contract has no trace of it whatsoever. A consumer reading the documentation cannot construct a valid registration request.

Key points:

- **Business value and user needs**: Consumers cannot infer a relationship between two fields; it must be stated. In the `#[Confirmed]` case they cannot even infer that a second field exists.
- **Relationship with other features**: Independent of the requirement and prohibition families. These attributes constrain a field's *value* relative to another field, not its presence.
- **Why this capability is needed now**: Registration, password change, and consent endpoints are among the first any integrator touches, and they are precisely the endpoints these attributes govern.

### Business Value

- Provide **API consumers** with the complete set of fields they must send, including confirmation fields that exist only as a consequence of a validation rule.
- Support **consent and compliance flows** by publishing exactly which values count as acceptance or refusal, so that legal-consent endpoints can be integrated correctly the first time.
- Enable **QA and integration teams** to test field-relationship rules from the documentation alone.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Output must remain valid for both.
- **Business constraints**: The package documents behaviour and must not change it.

### Scope In

- Documenting the field-to-field comparison attributes `#[Same]`, `#[Different]`, and `#[InArray]` as readable statements naming the referenced field.
- Documenting `#[Confirmed]`, including surfacing the companion confirmation field that the rule implies but that is not declared as a property.
- Documenting the acceptance attributes `#[Accepted]`, `#[AcceptedIf]`, `#[Declined]`, and `#[DeclinedIf]`, including the concrete set of values that count as acceptance or refusal.

### Scope Out

- Conditional requirement and prohibition attributes — covered by STORY-001-001 and STORY-001-002.
- Numeric and date comparisons against another field — date comparisons are covered by STORY-001-005; numeric bound attributes are already supported today.
- Enumerated value attributes such as `#[In]` and `#[Enum]` — covered by STORY-001-004.
- Any change to runtime validation behaviour.

### Acceptance Criteria

#### AC1: Equality against another field is documented

**Given** an endpoint accepts a Data object with a property `email_confirmation` annotated `#[Same('email')]`
**When** documentation is generated for that endpoint
**Then** the description of `email_confirmation` states that its value must match the value of `email`

#### AC2: Inequality against another field is documented

**Given** a property `new_password` annotated `#[Different('current_password')]`
**When** documentation is generated
**Then** the description of `new_password` states that its value must differ from the value of `current_password`

#### AC3: Membership in another submitted field's values is documented

**Given** a property `primary_tag` annotated `#[InArray('tags')]`
**When** documentation is generated
**Then** the description of `primary_tag` states that its value must be one of the values submitted in `tags`

#### AC4: A confirmation rule surfaces the companion field

**Given** a property `password` annotated `#[Confirmed]`
**When** documentation is generated for that endpoint
**Then** the published contract includes a parameter named `password_confirmation` in addition to `password`
**And** the description of `password` states that a matching `password_confirmation` value must be sent

#### AC5: A confirmation rule with a custom companion field name surfaces that name

**Given** a property `email` annotated `#[Confirmed('email_repeat')]`
**When** documentation is generated
**Then** the published contract includes a parameter named `email_repeat`
**And** the description of `email` states that a matching `email_repeat` value must be sent

#### AC6: Acceptance rule publishes the values that count as acceptance

**Given** a property `terms_accepted` annotated `#[Accepted]`
**When** documentation is generated
**Then** the description of `terms_accepted` states that it must be sent as one of `yes`, `on`, `1`, `"1"`, `true`, or `"true"`

#### AC7: Conditional acceptance rule states its condition

**Given** a property `marketing_opt_in` annotated `#[AcceptedIf('country', 'DE')]`
**When** documentation is generated
**Then** the description of `marketing_opt_in` states that it must be accepted when `country` is `DE`

#### AC8: Refusal rules are documented with their own value set

**Given** a property `data_sharing` annotated `#[Declined]`
**And** a property `auto_renew` annotated `#[DeclinedIf('plan', 'trial')]`
**When** documentation is generated
**Then** the description of `data_sharing` states that it must be sent as one of `no`, `off`, `0`, `"0"`, `false`, or `"false"`
**And** the description of `auto_renew` states that it must be declined when `plan` is `trial`

#### AC9: A comparison attribute alongside an already-supported attribute produces one coherent description

**Given** a property `email_confirmation` annotated with both `#[Same('email')]` and `#[Email]`
**When** documentation is generated
**Then** the description contains both the existing email-format sentence and the new matching-field sentence
**And** the sentences appear in a stable, repeatable order across successive documentation builds

#### AC10: A malformed or unrecognised attribute does not break the build

**Given** a property carries a comparison attribute referencing a field not present on the Data object
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- A confirmation field introduced by `#[Confirmed]` must be presented consistently with ordinary documented parameters, so that consumers and code generators treat it as a real part of the request contract.
- Repeated documentation builds over an unchanged codebase produce identical output.

## Domain Concept Identification

### Existing Concepts (from codebase)

- **Attribute processor registry** (`src/AttributeProcessing/AttributeProcessorRegistry.php`): a singleton map from an attribute's *exact* class to one processor. `AttributeProcessingStage` walks the property's package attributes, then its validation attributes in declaration order, and calls the processor for each one that has an entry. None of the eight attributes in this story has an entry today, so all of them are silently skipped. That is the whole cause of the gap the story describes. Because the lookup is by exact class, an application's subclass of an upstream attribute is also skipped. Related to: every new processor.

- **Field-reference rendering convention** (`Processors/Base/FieldReferenceProcessor.php`): the house rule that a *field name* is rendered bold-italic and a *value* is rendered monospaced. It also unwraps a Spatie `FieldReference` to its bare `name`, so relative (nested) references are shown as the name the developer wrote, not the resolved dotted path. Every sentence in this story names another field or a value set, so all of them inherit this convention. Related to: comparison statement, acceptance statement.

- **Neutral condition-rendering base** (`Processors/Base/ConditionProcessor.php`, extracted by STORY-001-002): guarded parameter extraction, field-name extraction, and value rendering. Value rendering covers enums (case name plus backing value), booleans, `null`, several values as "one of: …", and a degraded result when a compared value is an `ExternalReference`. `AcceptedIf` and `DeclinedIf` have exactly the shape `ProhibitedIf` already handles: one field and one compared value, which may be a bool, an enum or an `ExternalReference`. Related to: conditional acceptance/refusal statement.

- **Comparison base** (`Processors/Base/ComparisonProcessor.php`): the base the numeric field-or-value comparisons (`GreaterThan`, `LessThan`, …) extend. They already render a `FieldReference` operand as a field name ("Must be greater than ***min_price***."). It is the closest precedent for `Same`, `Different` and `InArray`. However, it also writes numeric constraints, which these three must not do. Related to: comparison statement.

- **Description assembly and ordering** (`AttributeProcessingStage`, then `RequirementDescriptionStage`): processor sentences are joined in attribute declaration order. The requirement sentences ("Must be included…", "Only validated when…", "A null value is accepted.", the never-satisfiable note) are appended afterwards. Declaration order is source order, so it repeats across builds. This already gives AC9 its stable order: the `#[Email]` sentence and the `#[Same]` sentence appear in the order they are declared. Related to: AC9, NFR 2.

- **Requirement status and its sole authority** (`Pipeline/Support/RequirementResolver.php`): the only component allowed to decide `required` and `nullable`. It **already** knows the acceptance family:
  - `Accepted` and `Declined` are treated as key-demanding (the key must be sent) and null-rejecting.
  - `AcceptedIf` and `DeclinedIf` are recognised as conditional implicit rules that do not subtract nullability.
  - `RequirementReconciliationTest` pins this against Laravel's validator.

  So this story adds only *value* sentences for the acceptance family. Requirement is already correct and must not be touched. Related to: acceptance statement, key business rules.

- **Parameter generation loop** (`src/Services/ParameterGenerator.php`): walks each Data property once, runs the pipeline on a per-property `ParameterContext`, and emits **exactly one** `Parameter` per non-hidden property. Nested and array Data are recursed with a dotted or `[]` prefix. There is no way today for one property to produce a second parameter, and no pass that looks across properties once they have all been processed. Related to: confirmation companion parameter, example coherence.

- **Parameter context and value object** (`Pipeline/Context/ParameterContext.php`, `ValueObjects/Parameter.php`): the context is bound to one real `DataProperty` (read-only). The `Parameter` value object is the unit that Scribe strategies, `ParameterFilter` (location and HTTP-method filtering) and the OpenAPI output consume. A parameter built as a real `Parameter` is presented identically to a declared one. That is what NFR 1 asks for. Related to: confirmation companion parameter.

- **Example generation** (`Pipeline/Stages/ExampleGenerationStage.php`): produces each property's example in isolation, with Faker. It knows nothing of other properties. A `boolean` gets a random `true`/`false`, and an email-format string gets a fresh random address. Related to: example coherence (see D6).

- **Upstream attributes** (`spatie/laravel-data` 4.x, `Attributes/Validation/`):
  - `Same`, `Different` and `InArray` each take one field (string or `FieldReference`) and emit it as their single parameter.
  - `Accepted` and `Declined` take nothing.
  - `AcceptedIf` and `DeclinedIf` take a field and **one** compared value (string, bool, int, float, `BackedEnum` or `ExternalReference`).
  - `Confirmed` **declares no constructor and always emits no parameters**. PHP accepts `#[Confirmed('email_repeat')]` and silently discards the argument (verified in the project container, Result 1).

  Related to: all new concepts.

- **Laravel validator semantics** (`Illuminate\Validation\Concerns\ValidatesAttributes`, verified in the project container, Result 2):
  - `confirmed` is `same:<attribute>_confirmation` unless a parameter names another field. On a nested key the companion is a sibling (`user.password` → `user.password_confirmation`).
  - `accepted` is a strict match against `'yes'`, `'on'`, `'1'`, `1`, `true`, `'true'`. It is case-sensitive: `'TRUE'` is rejected. `declined` matches `'no'`, `'off'`, `'0'`, `0`, `false`, `'false'`.
  - `in_array:<field>` matches by key pattern: `in_array:tags` **rejects** a value that is present in an array-valued `tags`, and only `in_array:tags.*` accepts it.
  - `same:<absent field>` rejects every non-null value. `different:<absent field>` always passes.

  Related to: comparison statement, AC3, AC10.

- **Hidden attribute and query-parameter location** (`HiddenStage`, `QueryParameterProcessor`, `ParameterFilter`): a hidden property is dropped from the contract. A query-located property is filtered into query parameters. Both decide where, or whether, a companion field appears. Related to: confirmation companion parameter.

### New Concepts Required

- **Field comparison statement** (`Same`, `Different`, `InArray`): a sentence that names one other field and states the value relationship: must match, must differ, or must be one of the values submitted in it. It is value-relative, not presence-relative (Background), so it never uses requirement wording and never touches requirement status or schema constraints. `InArray` needs care over the wildcard in its reference (see D4).

- **Confirmation statement**: a sentence on the *source* field (the one carrying `#[Confirmed]`) that tells the consumer to send a matching value under the companion name (AC4, AC5).

- **Confirmation companion parameter**: a documented request parameter that exists only because a rule implies it. It has no `DataProperty` behind it. It is derived from the source field: it sits beside the source (same prefix and location), matches its type and format, and carries its own sentence saying it must match the source. It is the first concept in the package where one property yields more than one published parameter. NFR 1 requires it to be indistinguishable from a declared parameter to consumers and code generators.

- **Acceptance and refusal value sets**: two fixed, published lists that mirror Laravel's strict sets exactly: `yes`, `on`, `1`, `"1"`, `true`, `"true"` and `no`, `off`, `0`, `"0"`, `false`, `"false"`. They are a named concept rather than inline strings because four processors share them, and because they must track Laravel's validator, not the story text.

- **Acceptance / refusal statement**: an unconditional sentence (`Accepted`, `Declined`) or a conditional one (`AcceptedIf`, `DeclinedIf`), stating the value set and, for the conditional form, the condition field and value. It reuses the neutral condition rendering, including the degraded wording for an `ExternalReference` compared value.

- **Relationship-consistent examples** (see D6): the principle that a published example request should satisfy the relationships it documents. For example, `password_confirmation`'s example equals `password`'s, and `terms_accepted`'s example is an accepted value. Some of this can be decided per property. Some of it can only be decided once every property of the Data object has been processed.

### Key Business Rules

- **Documentation only**: nothing in this story may change what is accepted or rejected. The new processors add sentences. The companion parameter is added to the *published* contract only. None of them may write requirement status. *Governs: all new concepts, `RequirementResolver`.*
- **Document what is enforced, not what is written** (implicit, surfaced here; this is the principle `RequirementResolver`'s docblock is built on): where a declaration and runtime disagree, the contract follows runtime. This decides AC5 (`Confirmed`'s argument is discarded) and the bare `InArray` reference (D4). *Governs: confirmation companion parameter, field comparison statement.*
- **The companion name is `<source>_confirmation`, beside the source**: this is the default Laravel applies. It is computed from the same name and prefix the package already publishes for the source, so a nested source `profile.password` has companion `profile.password_confirmation`, and an array source `users[].password` has `users[].password_confirmation`. *Governs: confirmation companion parameter.*
- **A declared property wins over a synthesised companion**: if the Data object already declares a property with the companion's name, that property is documented as normal and no second parameter is emitted. The source still gets its confirmation sentence. Emitting both would produce a duplicate key in the contract. *Governs: confirmation companion parameter.*
- **The companion inherits the source's visibility and location**: a hidden source yields no companion. A query-located source yields a query-located companion, so `ParameterFilter` keeps the two together. *Governs: confirmation companion parameter.*
- **The companion's requirement is derived from the source's, not resolved independently**: `confirmed` runs only when the source is validated. So the companion is required exactly when the source is required. When the source is optional, the companion is published as optional, with wording that it is needed whenever the source is sent. This is the one requirement decision outside `RequirementResolver`. It is allowed because the companion has no property for the resolver to read (see D3). *Governs: confirmation companion parameter.*
- **Acceptance values are exact and case-sensitive**: the published sets must list exactly Laravel's values, and must not imply that `TRUE`, `Yes` or `ON` are accepted. *Governs: acceptance / refusal statement.*
- **Acceptance sentences state values, not presence**: the resolver already publishes `#[Accepted]` and `#[Declined]` fields as required and not nullable. The new sentence must not repeat or contradict that. *Governs: acceptance / refusal statement.*
- **Stable order**: sentences follow attribute declaration order. A companion parameter immediately follows its source in the published parameter order. Repeated builds must be identical (NFR 2). *Governs: description assembly, parameter generation loop.*
- **Never fail the build over one attribute** (AC10): a reference to a field the Data object does not declare is still rendered by name. A malformed or opaque value (`ExternalReference`) degrades the sentence and does not throw. *Governs: all new processors.*

## Strategic Approach

### Solution Direction

- Follow the established one-processor-per-attribute pattern:
  - `Same`, `Different` and `InArray` get small field-reference processors on the `FieldReferenceProcessor` base (not `ComparisonProcessor`, which writes numeric bounds).
  - `Accepted` and `Declined` get processors that append a fixed value-set sentence.
  - `AcceptedIf` and `DeclinedIf` get processors on the neutral `ConditionProcessor`, mirroring `ProhibitedIf`, degraded wording included.
  - `Confirmed` gets a processor that appends the confirmation sentence and records the companion requirement on the context.
- Add one new capability to parameter generation: after a property's pipeline run, `ParameterGenerator` materialises any companion the context recorded as a real `Parameter`. The companion is derived from the source's finished parameter and emitted directly after it. Processors remain the only readers of attributes, and the generator remains the only emitter of parameters.
- Keep examples honest (D6): per-property choices (acceptance and refusal values) are made in the property's own pipeline run. The one cross-parameter choice this story needs (the companion copies the source's example) is made where the companion is created.
- Data flow: Data property → pipeline (processors append sentences, `Confirmed` records a companion) → `ParameterGenerator` emits source, then companion → `ParameterFilter` → Scribe body/query parameters → OpenAPI. No change to the OpenAPI glue: the companion arrives as an ordinary parameter.

### Key Design Decisions

- **D1 — How the companion parameter is produced**:
  - (a) Processor records it on the context; `ParameterGenerator` builds it from the source's finished parameter. Keeps "one reader, one emitter", and needs no fake upstream objects.
  - (b) Fabricate a synthetic `DataProperty` and run it through the full pipeline. This reuses every stage, but it means building upstream internals by hand, and the stages would re-read the source's attributes (including `Confirmed` itself).
  - (c) Emit it from the Scribe strategy layer. This is too late: nested prefixes and hidden/location decisions are already flattened by then, and a second code path would bypass `ParameterGenerator`.

  → **(a)**. The companion is a consequence of a rule, so it should be derived from the processed source, not generated from nothing.

- **D2 — AC5, custom companion name**: the premise does not hold against the installed laravel-data. `#[Confirmed('email_repeat')]` compiles, the argument is discarded, and runtime demands `email_confirmation`. `#[Rule('confirmed:email_repeat')]` loses it too, because Spatie's normaliser rebuilds `Confirmed` through a constructor-less `new static(...)` (verified, Result 1).
  - (a) Document what runtime enforces: `email_confirmation`. Take the companion name from `Confirmed::parameters()` when it is non-empty, which future-proofs for an upstream change.
  - (b) Read the raw attribute argument via reflection and publish `email_repeat`. This publishes a field the API never checks, and a consumer following the docs is rejected.
  - (c) (a) plus a developer-facing note when a discarded argument is detected. This is more helpful, but the argument is only visible through PHP reflection of the property, not through `DataProperty`.

  → **(a)**. AC5 must be re-worded or re-scoped by the story owner before implementation (open question Q1). (c) is a reasonable follow-up, not part of this story.

- **D3 — Companion requirement and nullability**: resolving them through `RequirementResolver` is impossible, because there is no property to read. Deriving them from the source is faithful to how `confirmed` runs. → **Derive**: companion `required` mirrors the source. When the source is optional, the companion is optional and its sentence says it is needed whenever the source is sent. Companion `nullable` mirrors the source. Document this as the single sanctioned exception to "only the resolver decides requirement".

- **D4 — `InArray` reference with and without a wildcard**: the rule accepts a member of an array field only when referenced as `tags.*`. A bare `tags` compares against the key `tags` itself, and rejects every value when `tags` is an array (verified, Result 2).
  - (a) Always publish AC3's "one of the values submitted in ***tags***". This matches the author's intent, but contradicts runtime for the bare form.
  - (b) Publish AC3's sentence for the wildcard form, with the trailing `.*` stripped for display. Publish a literal "must equal the value of ***tags***" for the bare form.
  - (c) (b) plus a never-satisfiable-style note when the bare reference names a declared array property.

  → **(b)**, following "document what is enforced". (c) needs cross-property knowledge. It is deferred, and noted as a follow-up in the same family as the existing never-satisfiable note. AC3's example should be corrected to `#[InArray('tags.*')]` (Q2).

- **D5 — Acceptance wording for the conditional forms**: AC7 and AC8 only require "must be accepted when…". Restating the value set in the conditional sentence makes it self-contained, but longer. → **Restate the set**. A consumer reading `marketing_opt_in` alone must learn which values count, and the sets are a shared concept, so there is no drift risk.

- **D6 — Examples that contradict documented relationships**: today `#[Accepted] bool` gets a random boolean, which is `false` half the time. `#[Declined] bool` is `true` half the time. A companion or `#[Same]` field gets a fresh Faker value that differs from its source. So the published example request fails validation.
  - (a) In scope: an acceptance or refusal field gets a value from its set. The companion copies the source's example. Both are local and cheap.
  - (b) Also align `#[Same]` with its target, and `#[InArray]` with an element of its target's example. This needs a cross-property pass after all properties are generated, which is new machinery.
  - (c) Leave examples alone. This contradicts NFR 1's "treated as a real part of the request contract".

  → **(a) now**. Record (b) as a follow-up, because it introduces a Data-object-level post-pass that later stories (date comparisons, STORY-001-005) are likely to want too, and that pass deserves its own design. The ACs do not require examples; flag this for the story owner (Q4).

- **D7 — Schema type for acceptance fields**: a `bool` property with `#[Accepted]` is published as `type: boolean`, yet validation also accepts `"yes"` and `"on"`. Widening the schema type would change the contract for code generators, and misstate what Laravel Data can construct. → **Description only**. The type stays as the PHP type implies. The value set is stated in prose.

### Alternatives Considered

- **Treating `Same`/`Different` as `ComparisonProcessor` subclasses**: rejected. That base exists to write numeric bounds (`exclusiveMinimum` and so on), and equality against another field has no schema representation.
- **Marking the `#[Same]` field as an alias or `readOnly` of its target in the schema**: rejected. OpenAPI has no field-equality keyword, and any encoding would be non-standard and ignored by code generators.
- **Documenting the companion only as a sentence on the source (no parameter)**: rejected. AC4 and NFR 1 explicitly require a real parameter, and a code generator ignores prose.
- **Generalising a "derived parameter" framework now** (for future rules that imply fields): rejected as speculative. `Confirmed` is the only such rule in scope. A narrow, companion-specific mechanism is easier to review and can be generalised when a second case appears.
- **Resolving relative `FieldReference`s to full dotted paths in sentences**: rejected for consistency. STORY-001-001 and STORY-001-002 render the name as written. Changing it here alone would make sentences within one Data object inconsistent.

## Risk & Gap Analysis

### Requirement Ambiguities

- **Q1 — AC5 is not achievable as written**: runtime ignores the custom name and enforces `email_confirmation` (D2). The story owner must choose one of three options:
  - Re-word AC5 to "`#[Confirmed('email_repeat')]` is documented as requiring `email_confirmation`, because that is what is enforced".
  - Replace AC5 with a mechanism that genuinely reaches runtime (none exists in laravel-data 4.x).
  - Drop AC5.
- **Q2 — AC3's example uses the bare `tags` reference**: this form never accepts a member of an array-valued `tags`. Confirm the intended declaration is `#[InArray('tags.*')]`, and that the bare form should be documented literally (D4).
- **Q3 — Companion wording and requirement for an optional source**: AC4 is silent on whether `password_confirmation` is "required". D3 proposes mirroring the source, with a "needed when ***password*** is sent" sentence for an optional source. Confirm.
- **Q4 — Are examples in scope?** The ACs only mention descriptions and parameter presence. D6 recommends fixing acceptance and companion examples now, and deferring `Same`/`InArray` alignment.
- **Value-set rendering in AC6/AC8**: the story writes `1` and `"1"` as distinct entries, which matches Laravel's int-versus-string strictness. Confirm that the published text should keep that distinction (and the JSON-style quoting), rather than a flat `yes, on, 1, true`.
- **AC7 phrase "must be accepted"**: whether "accepted" is enough on its own, or the value set must be restated (D5 recommends restating).
- **Field names in sentences**: sentences name the referenced field as the developer wrote it (a property name, or a relative reference in nested Data). The package does not apply `MapInputName`/`MapName` anywhere today, so a mapped property is already published under its PHP name. The companion inherits that behaviour. Confirm this pre-existing gap stays out of scope.

### Edge Cases

- **Companion name collides with a declared property** (`password` has `#[Confirmed]` and the DTO also declares `password_confirmation`): the declared property must win, with no duplicate. The source's sentence still names it.
- **Hidden source or query-located source**: the companion must follow (none, or query). Otherwise `ParameterFilter` splits the pair, or leaks a field for a hidden one.
- **Nested and array sources**: `profile.password` and `users[].password` need a sibling companion under the same prefix, emitted directly after the source, before the next property. This matters because `ParameterGenerator` merges nested parameters in sequence.
- **`#[Confirmed]` on a non-string property** (for example an integer PIN): the companion must carry the source's type and format, not a default `string`.
- **`#[Confirmed]` on a nested Data object or array property**: this is legal but odd, since `same` compares whole structures. The companion would be an `object`. Decide whether to support it or to emit the sentence only.
- **`#[Same]` or `#[InArray]` referencing a field the Data object does not declare** (AC10): the build must not fail. Note that at runtime `same:<absent>` rejects every non-null value, while `different:<absent>` always passes. The sentence is still truthful ("must match ***x***"), so no warning is required, but it is a candidate for a later advisory.
- **`#[Same('email')]` on `email` itself**: this is trivially true and harmless. Render it as written.
- **Self-contradictory pairs**: `#[Same('a'), Different('a')]` can never pass, and `#[Accepted, Declined]` likewise. Both sentences are rendered. There is no advisory in this story; note it alongside the never-satisfiable precedent.
- **`AcceptedIf`/`DeclinedIf` with a boolean, enum or `ExternalReference` compared value**: covered by the neutral rendering (bool, enum name plus value, degraded wording). `create()` in both classes converts `'true'`/`'false'` strings to booleans, so a `#[Rule('accepted_if:x,true')]`-sourced attribute arrives as bool.
- **`#[Accepted]` combined with `#[Nullable]` or a nullable type**: the resolver already publishes it as not nullable (implicit rule rejects null). The new sentence must not re-open that.
- **`#[Declined] bool` with a string `"false"`**: validation passes. Whether Laravel Data then constructs `true` (non-empty string coercion) was **not verified**. It is behaviour, not documentation, so it is out of scope, but worth a note to the story owner.
- **Duplicate attributes** (for example two `#[Same]` on one property, if the target allows repetition): each yields its own sentence, in declaration order.

### Technical Risks

- **First one-to-many property mapping**: tests and downstream code may assume that the parameter count equals the property count, or that each parameter key maps to a `DataProperty`. Mitigation: confine companion creation to `ParameterGenerator`, and cover ordering, collision, prefix, hidden and location in integration tests.
- **Requirement authority exception (D3)**: the companion's `required`/`nullable` is decided outside `RequirementResolver`. Mitigation: derive it strictly from the source's already-resolved values, never from attributes, and state the exception in the resolver's or generator's documentation so later stories do not copy it loosely.
- **Upstream drift in `Confirmed`**: if laravel-data adds a constructor parameter, D2(a) picks it up through `parameters()` automatically. Pin that with a test on the parameter-driven path, so a silent upstream change surfaces.
- **Laravel value-set drift**: the acceptance and refusal sets are copied from Laravel's validator. Mitigation: a test that runs Laravel's validator over every published value, and over near-misses (`'TRUE'`, `'Yes'`), in the style of `RequirementReconciliationTest`.
- **Exact-class registry**: subclasses of these attributes stay undocumented. This is pre-existing and consistent with Stories 1–2. Note it, do not fix it.
- **Output validity**: sentences contain inline HTML (`<code>`, `<b><i>`), as existing descriptions do. The companion parameter must pass through the same `Parameter::toArray()` path, so that both OpenAPI and HTML output stay valid (Integration points).

### Acceptance Criteria Coverage

| AC# | Description | Addressable? | Gaps/Notes |
|-----|-------------|--------------|------------|
| 1 | `#[Same('email')]` states value must match `email` | Yes | Field comparison statement; example alignment deferred (D6b). |
| 2 | `#[Different('current_password')]` states value must differ | Yes | Field comparison statement. |
| 3 | `#[InArray('tags')]` states value must be one of `tags` values | Partial | True only for `tags.*`; the bare form is documented literally per D4. The AC example needs correcting (Q2). |
| 4 | `#[Confirmed]` surfaces `password_confirmation` and a matching-value sentence | Yes | Needs the new companion capability (D1). Requirement wording for an optional source needs confirming (Q3). |
| 5 | `#[Confirmed('email_repeat')]` surfaces `email_repeat` | No (as written) | Upstream discards the argument; runtime enforces `email_confirmation` (D2). Needs a story-owner decision (Q1). |
| 6 | `#[Accepted]` lists `yes`, `on`, `1`, `"1"`, `true`, `"true"` | Yes | Shared value set; requirement is already published by the resolver. |
| 7 | `#[AcceptedIf('country','DE')]` states condition | Yes | Neutral condition rendering; value set restated (D5). |
| 8 | `#[Declined]` value set and `#[DeclinedIf('plan','trial')]` condition | Yes | Mirrors AC6/AC7. |
| 9 | `#[Same]` + `#[Email]` give both sentences in stable order | Yes | Already guaranteed by declaration-order assembly. |
| 10 | Reference to an undeclared field does not break the build | Yes | Names are rendered as written, with no lookup. Degraded wording for `ExternalReference`. |

### Verification Results (working evidence)

- **Result 1**: in the project container (PHP 8.4, laravel-data 4.x), `#[Confirmed('email_repeat')]` instantiates to a `Confirmed` with no state. Its class has no constructor, and `parameters()` returns `[]`. `ValidationRuleFactory` builds `Confirmed` for the keyword `confirmed` via `new static(...$parameters)`, so the string-rule path also drops the name.
- **Result 2**: against Laravel's validator:
  - `in_array:tags` rejects `'a'` for `tags = ['a','b']`, and `in_array:tags.*` accepts it.
  - `confirmed` on `user.password` accepts `user.password_confirmation`.
  - `accepted` rejects `'TRUE'`.
  - `same:zzz` with `zzz` absent rejects `'x'`, and `different:zzz` accepts it.

## Stakeholder Decisions (2026-09-25)

The open questions above were put to the story owner before REASONS Canvas generation. Each resolution below replaces the matching open question.

- **Q1 — AC5 → D2(a), document runtime.** `#[Confirmed('email_repeat')]` is documented as requiring `email_confirmation`, which is what the API enforces. The companion name is taken from `Confirmed::parameters()` when that is non-empty, so a future upstream parameter is picked up automatically. AC5 is re-worded to this effect. The developer warning (D2(c)) is out of scope.
- **Q2 — AC3 → D4(b).** `#[InArray('tags.*')]` renders the AC3 sentence, with `.*` stripped for display. A bare `#[InArray('tags')]` renders the literal "must equal the value of ***tags***". AC3's example is corrected to `tags.*`. The cross-property warning (D4(c)) is deferred.
- **Q3 — Companion requirement → D3, mirror the source.** `password_confirmation` is required exactly when `password` is. For an optional source, it is published as optional with a "required when ***password*** is sent" sentence. Nullable is mirrored.
- **Q4 — Examples → D6(a).** In scope: `Accepted`/`Declined` fields get an example drawn from their value set, and the companion copies the source's example. Aligning `Same` and `InArray` examples with their targets (D6(b)) is a follow-up.
- **Value-set rendering → as in AC6/AC8.** Int and string forms are kept distinct with JSON-style quoting (`yes`, `on`, `1`, `"1"`, `true`, `"true"`), each value rendered as code.
- **Conditional wording → D5, restate the set.** `AcceptedIf`/`DeclinedIf` sentences include the full value set alongside the condition.
- **Input-name mapping → out of scope.** Sentences and the companion use PHP property names, consistent with the rest of the package. `MapInputName`/`MapName` support is a separate follow-up.

Revised AC coverage: AC3 is **Yes** (against the corrected example), and AC5 is **Yes** (against the re-worded criterion). All 10 ACs are addressable.

The story file has been updated to match these decisions. AC3's example now uses `tags.*`, and AC5 now specifies `email_confirmation`. Two acceptance criteria were added:

| AC# | Description | Addressable? | Gaps/Notes |
|-----|-------------|--------------|------------|
| 11 | Companion requirement and nullability mirror the source; optional source → optional companion with "required when … is sent" | Yes | D3; the one requirement decision outside `RequirementResolver`. |
| 12 | `Accepted`/`Declined` examples come from their value sets; the companion example equals the source example | Yes | D6(a). `Same`/`InArray` example alignment is added to Scope Out as a follow-up. |

Revised coverage: 12/12.

## Correction (2026-09-25, after CI)

Result 1 was run on PHP 8.4 only, so "PHP accepts `#[Confirmed('email_repeat')]` and silently discards the argument" holds for PHP 8.4 alone. On PHP 8.3, `ReflectionAttribute::newInstance()` throws `Error: Attribute class … does not have a constructor, cannot pass arguments`, so Laravel Data cannot build a Data class that carries the declaration. That breaks the application and documentation alike. CI caught it on `P8.3 - L13.* - prefer-stable - ubuntu-latest`, and it was reproduced on PHP 8.3.33 and 8.4.21. Plain `new` with arguments is silent on both versions, so the `#[Rule('confirmed:…')]` path still drops the name everywhere. D2 stands for PHP 8.4. On PHP 8.3 there is no enforced contract to document, and the upstream error is left to surface.
