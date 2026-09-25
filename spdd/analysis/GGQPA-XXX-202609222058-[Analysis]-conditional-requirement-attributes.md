# SPDD Analysis: Conditional Requirement Attribute Documentation

## Original Business Requirement

### Referenced document: `requirements/[User-story-1]conditional-requirement-validation-attributes.md`

# [STORY-001-001] Conditional Requirement Attribute Documentation

**Estimated effort**: 2 days
**Prerequisite**: STORY-001-000 (requirement status reconciliation). Without it, a conditional attribute's effect on requirement status is overwritten later in generation.

### Background

When an API consumer reads the generated documentation for an endpoint, the most important question they ask about each field is "do I have to send this?". Laravel Data lets a developer answer that conditionally — a field may be required only when another field holds a particular value, or only when a companion field is absent or present.

None of those conditional attributes reach the published contract. A property carrying `#[RequiredIf('account_type', 'business')]` appears with no indication that it is ever required, let alone under what circumstance. Consumers discover the rule by submitting a request and receiving a validation error, which is exactly the failure the documentation exists to prevent.

Two further attributes, `#[Present]` and `#[Filled]`, express presence semantics that have no equivalent in the type system and are therefore invisible today: one requires the field to be included but permits it to be empty, the other permits omission but forbids an empty value when included.

Key points:

- **Business value and user needs**: Polymorphic request shapes, where the required fields depend on a discriminator field, cannot be integrated from the documentation at all today.
- **Relationship with other features**: STORY-001-000 establishes the correct baseline requirement status. This story adds the conditional cases on top of that baseline and names the fields each condition depends on.
- **Why this capability is needed now**: Conditional requirement is the most frequently used family in the uncovered set, so it delivers the largest reduction in consumer confusion per day of work.

### Business Value

- Provide **API consumers** with the circumstances under which each optional field becomes mandatory, so that conditional request shapes can be built without trial and error.
- Support **polymorphic payloads** — where a type discriminator determines which fields are required — in the published API contract.
- Enable **support and integration teams** to answer "why was my request rejected?" from the documentation alone.

### Dependencies and Assumptions

- **Prerequisites**: STORY-001-000. Conditional attributes influence requirement status, and until reconciliation is in place that influence is discarded before documentation is published.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Output must remain valid for both.
- **Business constraints**: The package documents behaviour and must not change it.

### Scope In

- Documenting `#[RequiredIf]` and `#[RequiredUnless]`, including a readable sentence naming the field the condition depends on and the value it is compared against.
- Documenting `#[RequiredWith]`, `#[RequiredWithAll]`, `#[RequiredWithout]`, and `#[RequiredWithoutAll]`, including a readable sentence naming the field or fields whose presence or absence triggers the requirement.
- Documenting `#[Present]` and `#[Filled]`, whose distinction has no equivalent in the type system.

### Scope Out

- Reconciling `#[Required]`, `#[Nullable]`, and `#[Sometimes]` with the declared type, and the pipeline ordering that makes such reconciliation possible — covered by STORY-001-000.
- Prohibition and exclusion attributes (`#[Prohibited]`, `#[Prohibits]`, `#[ExcludeIf]`, and relatives) — covered by STORY-001-002.
- Cross-field equality and comparison attributes (`#[Same]`, `#[Different]`, `#[Confirmed]`) — covered by STORY-001-003.
- `#[RequiredArrayKeys]`, which concerns the shape of an array value rather than the presence of a property — covered by STORY-001-007.
- Any change to runtime validation behaviour.
- Translating generated sentences into languages other than English.

### Acceptance Criteria

#### AC1: Value-conditional requirement is documented as optional with its condition stated

**Given** an endpoint accepts a Data object with a property `company_name` annotated `#[RequiredIf('account_type', 'business')]`
**When** documentation is generated for that endpoint
**Then** `company_name` is listed as optional
**And** its description states that it is required when `account_type` is `business`

#### AC2: Inverted value-conditional requirement is documented

**Given** a property `reason` annotated `#[RequiredUnless('status', 'approved')]`
**When** documentation is generated
**Then** `reason` is listed as optional
**And** its description states that it is required unless `status` is `approved`

#### AC3: Requirement conditional on the presence of another field is documented

**Given** a property `card_cvc` annotated `#[RequiredWith('card_number')]`
**And** a property `shipping_city` annotated `#[RequiredWithAll(['shipping_street', 'shipping_country'])]`
**When** documentation is generated
**Then** the description of `card_cvc` states that it is required when `card_number` is present
**And** the description of `shipping_city` states that it is required when both `shipping_street` and `shipping_country` are present

#### AC4: Requirement conditional on the absence of another field is documented

**Given** a property `phone` annotated `#[RequiredWithout('email')]`
**And** a property `fallback_contact` annotated `#[RequiredWithoutAll(['email', 'phone'])]`
**When** documentation is generated
**Then** the description of `phone` states that it is required when `email` is not present
**And** the description of `fallback_contact` states that it is required when neither `email` nor `phone` is present

#### AC5: Presence-only requirements are distinguished from value requirements

**Given** a property `terms` annotated `#[Present]`
**And** a property `title` annotated `#[Filled]`
**When** documentation is generated
**Then** the description of `terms` states that the field must be included in the request but may be empty
**And** the description of `title` states that, when included, the field must not be empty

> **Narrowed after review (2026-09-24).** The "may be empty" statement is true for `terms` only if it is a nullable string: with Laravel's default `ConvertEmptyStringsToNull`, an empty value on a non-nullable property becomes `null` and is rejected. `#[Present] string $terms` is published as required without the statement; `#[Present] ?string $terms` carries it. Recorded under Provenance in the pipeline canvas (`spdd/prompt/GGQPA-XXX-202608281600-[Codify]-pipeline-parameter-metadata.md`, Approach).

#### AC6: Several requirement attributes on one property produce one coherent description

**Given** a property `vat_number` annotated with both `#[RequiredIf('account_type', 'business')]` and `#[Nullable]`
**When** documentation is generated
**Then** `vat_number` carries the requirement status established by STORY-001-000
**And** its description contains both the conditional requirement sentence and the statement that a null value is accepted
**And** the two sentences appear in a stable, repeatable order across successive documentation builds

> **Narrowed after review (2026-09-25).** The null statement is true only if `vat_number`'s PHP type admits null. `#[RequiredIf] #[Nullable] ?string $vat_number` carries it. `#[RequiredIf] #[Nullable] string $vat_number` does not, because Laravel Data cannot build the property from null. Recorded under Provenance in the pipeline canvas (`spdd/prompt/GGQPA-XXX-202608281600-[Codify]-pipeline-parameter-metadata.md`, Approach).

#### AC7: A malformed or unrecognised requirement attribute does not break the build

**Given** a Data object property carries a conditional requirement attribute whose referenced field does not exist on that object
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- Generated condition sentences must be understandable by an API consumer who has never seen the application's source code — they name fields by the same names that appear in the documented request payload.
- Repeated documentation builds over an unchanged codebase produce identical output, so that generated API reference files can be committed and diffed meaningfully.

---

## Domain Concept Identification

### Existing Concepts (from codebase)

- **Requirement status** (`src/Pipeline/Support/RequirementStatus.php`): the package's answer to "must I send this field?", carrying three booleans — `required`, `nullable`, `onlyValidatedWhenPresent`. Introduced by STORY-001-000, which shipped on the current branch. It is a flat, unconditional answer: there is no place in it for "required, but only when something else holds". Related to: requirement derivation, published contract.

- **Requirement derivation** (`src/Pipeline/Support/RequirementResolver.php` + `src/Pipeline/Stages/RequiredStage.php`): the single authority on requirement status, and the sole holder of the coupling to Laravel Data's validation support namespace. It runs the configured rule inferrers without a payload and reads the resulting rule collection through one question — *does any rule implement the `RequiringRule` marker?* Its own docblock declares it assigns requirement fields unconditionally, so no attribute processor may contribute to them. Related to: requiring rule marker, pipeline ordering.

- **Requiring rule marker** (`Spatie\LaravelData\Support\Validation\RequiringRule`): an empty marker interface implemented by `Required` **and by all six conditional attributes in this story's scope**. It is deliberately undiscriminating — Laravel Data uses it only to decide which rules mutually displace one another, never to decide what a consumer should be told. This conflation is the single most consequential fact this analysis surfaces. Related to: requirement derivation.

- **Attribute processor and registry** (`src/AttributeProcessing/`): the package's translation layer from one validation attribute class to documentation output, a process-global singleton keyed by attribute class name. It is the package's public, README-documented extension point. It already registers no processor for `Required`, `Nullable`, `Sometimes`, `Present` or `Filled`. Related to: description accumulator, parameter context.

- **Cross-field reference handling** (`src/AttributeProcessing/Processors/Base/ComparisonProcessor.php`): an existing base class whose sole job is to turn a `FieldReference` — Laravel Data's wrapper around "the name of another field" — into a printable name. The seam this story needs for naming dependent fields already exists, unused by any requirement-related code. Related to: attribute processor.

- **Description accumulator** (`ParameterContext::$descriptions` and `$description`): an ordered list of sentences appended by processors, folded into one string by `AttributeProcessingStage`, then further appended to by three later stages — type, default value, and requirement. Sentence order is therefore decided in four places, not one. Related to: pipeline ordering.

- **Pipeline ordering** (`src/Pipeline/PipelineFactory.php`): a fixed stage sequence in which attribute processing runs early and requirement derivation runs late and overwrites. This ordering is the documented reason that requirement semantics cannot be expressed as a processor.

- **Requirement sentence emission** (`src/Pipeline/Stages/RequirementDescriptionStage.php`): the only existing precedent for turning a requirement flag into consumer-facing prose. Today it emits exactly one sentence, for the "only validated when included" case.

- **Published contract** (`src/ValueObjects/Parameter.php` and `src/Services/ParameterGenerator.php`): the output record, carrying a boolean `required`, a boolean `nullable`, a free-text `description`, and a bag of OpenAPI attributes. Parameter names are taken from the PHP property name and prefixed with the dotted path for nested Data objects. Related to: field naming.

### New Concepts Required

- **Conditional requirement**: a requirement that holds only under a stated circumstance. It is genuinely new because the published contract has no vocabulary for it — `required` is a boolean, and OpenAPI's `required` is likewise a flat list of property names. The concept must therefore be split across two carriers: the boolean says "you may omit this in some valid request", and prose says when you may not. It relates to requirement status as a qualifier the current three booleans cannot express.

- **Condition dependency**: the field or fields a conditional requirement is contingent on, together with the value it is compared against. This is the first time the package must name *another property* in the description of the property being documented, which makes it the first place where the package's parameter-naming policy becomes externally visible in prose rather than only in keys.

- **Presence-versus-value semantics**: the distinction between a field having to appear in the request and a field having to carry a value. `#[Present]` asserts the first and waives the second; `#[Filled]` waives the first and asserts the second. Neither maps onto `required`/`nullable`, which is precisely why the story calls them invisible today. It relates to requirement status as an orthogonal axis rather than a refinement of it.

- **Condition sentence**: the rendered, consumer-readable form of a condition dependency. It is new as an output artefact with its own stability contract — the non-functional expectations make its exact wording and its position among sibling sentences part of the deliverable, not an implementation detail.

### Key Business Rules

- **Documentation must not contradict runtime validation** — inherited from STORY-001-000, and this story is the first place it becomes genuinely hard, because the faithful runtime answer ("a requiring rule is present") and the faithful consumer answer ("you can send a valid request without this field") differ. Governs requirement status and conditional requirement.

- **A conditionally required field is published as optional and carries its condition** — the two halves are inseparable. Publishing it as optional without the sentence is a regression in information; publishing the sentence without flipping the boolean leaves the contract self-contradictory. Governs conditional requirement, published contract.

- **Presence is not value, and value is not presence** — `#[Present]` and `#[Filled]` must be documented as distinct, and neither may be collapsed into `required`. Governs presence-versus-value semantics.

- **One property yields one coherent description in a stable order** — governs condition sentence and the description accumulator, and is the rule most at risk given that four separate stages append to the same string.

- **Documentation generation never fails on a declaration it does not understand** — a malformed, dangling or unrecognised requirement attribute must degrade to silence, not to a broken build. Governs condition dependency and the attribute processor registry.

- **Generated output is byte-identical across runs over an unchanged codebase** — governs condition sentence ordering and any value rendering that could reach outside the source code.

- **Condition sentences name fields as the consumer sees them** — governs condition dependency and the published contract's naming policy.

---

## Strategic Approach

### Solution Direction

**The story is not additive. It is a correction of a defect that STORY-001-000 introduced, and the correction lands in the pipeline, not only in the processor registry.**

The prerequisite is satisfied — requirement reconciliation shipped in commit `1378504` and its acceptance test passes. But reconciliation asks the rule collection exactly one question: *does any rule implement `RequiringRule`?* All six conditional attributes in this story's scope implement that marker. The consequence, reproduced by running the current pipeline (see **Empirical Verification**), is that every conditionally required property is now published as **required: true** — including properties whose declared type admits null, which the package published as optional before STORY-001-000.

This inverts the story's own framing. The background section says a conditionally required property "appears with no indication that it is ever required". That was true of the released package; on the current branch the property appears as **unconditionally required**, which is worse. A consumer building strictly from today's documentation will send `company_name` on every request, including the account types where the API neither needs nor expects it. The story's business case therefore understates its urgency, and its first four acceptance criteria are defect corrections rather than enhancements.

The recommended direction follows from that, and has two seams:

1. **Requirement derivation gains a distinction the marker interface does not make** — between a rule that requires a field always and one that requires it only sometimes. This belongs inside the requirement authority, because `RequiredStage`'s own docblock establishes that anything an earlier stage writes to the requirement fields is discarded. This is the pipeline canvas.

2. **Condition sentences are produced by registry processors**, one per attribute family, exactly as the spike recommended and exactly as every other documented attribute family works. The structured data needed is already on the attribute objects, and the `FieldReference`-to-name seam already exists. This is the attribute-processors canvas.

**This makes STORY-001-001 a cross-canvas story, which the spike analysis predicted it would not be.** The spike's decomposition asserted that after moving reconciliation out, STORY-001-001 would "stay within a single canvas". That was a reasonable inference from static reading, but it assumed the conditional attributes would arrive at the requirement stage indistinguishable from nothing at all. In fact they arrive indistinguishable from `#[Required]`. The estimate of 2 days should be revisited on that basis.

### Key Design Decisions

- **Where the conditional carve-out lives**: inside requirement derivation, versus a processor registered for the six attribute classes, versus a new pipeline stage after `RequiredStage`. Trade-offs: a processor is the cheapest and matches the registry's granularity, but its output is provably overwritten — this is the exact ordering defect STORY-001-000 was created to fix, and re-introducing it would be a silent regression. A new stage after `RequiredStage` works but creates a second authority on `required`, contradicting the "sole authority" invariant the shipped docblock states. → **Put it in requirement derivation.** The requirement fields keep exactly one writer.

- **How "conditional" is recognised**: an explicit set of attribute classes, versus a structural test on the rule object, versus a new marker of the package's own. Trade-offs: no structural test exists — `RequiredIf` and `Required` are distinguishable only by class, since the marker interface is empty and `parameters()` is not part of any shared contract. An explicit set is a closed list that must grow when Laravel Data adds an attribute, but it is honest about being one, and it makes the fallback safe. → **Use an explicit set, with an unrecognised `RequiringRule` continuing to mean unconditionally required.** That fallback preserves today's behaviour for anything unknown rather than guessing optional, which would risk publishing a mandatory field as optional — the failure mode STORY-001-000 exists to eliminate.

- **What `required` publishes for a conditionally required field**: `false` per AC1–AC4, or `true` per the runtime rule set. Trade-offs: `true` is literally faithful to the inferred rules and preserves the "never contradict runtime" rule in its narrowest reading; `false` is faithful to what a consumer can observe, is the only value OpenAPI can express for a field that is required in some requests and not others, and is what the story mandates. → **Publish `false`, and carry the full truth in the description.** Record explicitly that the "never contradict runtime" rule is being read as "never mislead a consumer about what a valid request looks like", because the narrow reading and this story's ACs cannot both be satisfied.

- **What `required` publishes for `#[Present]`**: this is an open question the story does not settle, and exploration shows the current answer is wrong in the opposite direction. `#[Present]` causes Laravel Data to *remove* every requiring rule, so a non-nullable `#[Present]` property is published today as **required: false** while the API rejects any request that omits the key. AC5 asks only for a description sentence, which would leave `required: false` sitting beside the words "must be included in the request". Trade-offs: flipping it to `true` makes the boolean mean what OpenAPI's `required` means — the key must appear — and removes the contradiction; leaving it alone keeps this story purely additive and defers a second boolean change. → **Recommend flipping it to `true`, and flag it for explicit sign-off**, because it is a contract change the story's scope statement does not authorise and it will show up as a diff in every consumer's generated output.

- **How the dependent field is named**: rendered verbatim from the attribute, versus resolved against the documented parameter name. Trade-offs: verbatim is deterministic, needs no sibling lookup, and satisfies AC7 trivially — but it can print a name that appears nowhere in the published payload, because parameter names come from the PHP property name while the reference string is whatever the developer wrote, and nested properties are published under a dotted path the reference does not carry. Resolving is more faithful to the non-functional expectation but requires walking sibling properties, which is where AC7's dangling-reference case actually bites. → **Render verbatim in this story**, and record the naming mismatch as a known limitation with a follow-up, because the package's parameter-naming policy (property name versus input-mapped name) is a separate unresolved question that this story should not be made to answer.

- **How compared values are rendered**: the value list on `#[RequiredIf]`/`#[RequiredUnless]` is variadic and may hold plain strings, backed enums, or an `ExternalReference` that resolves from the container or configuration at validation time. Trade-offs: resolving an external reference at documentation time makes output depend on the environment, which breaks the byte-identical-builds expectation and risks leaking an internal identifier into a public document. → **Render literal values and enum values; do not resolve external references**, and decide deliberately what the sentence says when the value cannot be shown.

- **Where condition sentences sit in the description**: appended through the existing ordered accumulator alongside other attribute-derived sentences, versus emitted by a dedicated late stage next to the existing requirement sentence. Trade-offs: the accumulator gives source-declaration order, which exploration confirms is stable across runs; a late stage gives a guaranteed position relative to the type and default sentences but adds a fifth writer to one string. → **Use the accumulator**, and pin the resulting full sentence sequence with a test, since AC6 makes order a deliverable.

### Alternatives Considered

- **Register processors for the six attributes and leave requirement status untouched**: rejected. It satisfies the description half of AC1–AC4 while leaving `required: true`, producing documentation that says "optional, required when account type is business" next to a contract flag marking the field mandatory. The self-contradiction is worse than the current gap.

- **Reuse the existing `onlyValidatedWhenPresent` flag to carry conditionality**: rejected. It already has a distinct meaning and an attached sentence ("Only validated when included in the request"), which would be emitted incorrectly for conditionally required fields. Exploration also shows the two interact: adding any requiring rule *removes* `Sometimes` from the rule set, so the flag is not even available on a conditionally required property.

- **Express the condition structurally in the OpenAPI output** (`allOf`/`if`-`then`, or a vendor extension): rejected for this story. The story asks for a readable sentence, the downstream Scribe parameter model has no slot for a conditional schema, and both named consumers — OpenAPI descriptions and human-readable reference pages — are served by prose. Worth revisiting once the whole requirement module has landed.

- **Delegate the conditional/unconditional distinction to Laravel Data**: rejected because there is nothing to delegate to. The distinction does not exist upstream — the marker interface is empty by design, and no inferrer or resolver draws the line this story needs. This is the one place in the module where the package must own a judgement rather than inherit one, and saying so explicitly is better than discovering it during implementation.

---

## Risk & Gap Analysis

### Requirement Ambiguities

- **"Listed as optional" contradicts the inherited-precedence principle**: STORY-001-000 established that the package never invents requirement semantics. AC1–AC4 require the package to publish `optional` for properties whose inferred rule set contains a requiring rule. Both cannot hold in their strict form. The story needs an explicit statement of which reading of "must not contradict runtime validation" governs.

- **AC6 asks for a sentence the package has never produced**: it requires the description to contain "the statement that a null value is accepted". No such prose exists anywhere in the source — nullability is published solely as a boolean, and STORY-001-000's shipped acceptance test asserts the boolean, not a sentence. Either AC6 is asking for a new nullable sentence (which is arguably STORY-001-000's territory and would change output for every nullable property in every consumer's documentation), or "statement" means the boolean flag. This must be settled before design; it is the one ambiguity that materially changes the size of the story.

- **AC6's requirement status is under-determined**: it says `vat_number` "carries the requirement status established by STORY-001-000", but STORY-001-000 yields `required: true` for that declaration, while AC1 yields optional for the same conditional attribute. The AC cannot be verified as written until the carve-out decision above is recorded.

- **AC5 is silent on requirement status**: it specifies the description for `#[Present]` and `#[Filled]` but not what `required` should publish, which is exactly where the current output is wrong for `#[Present]`.

- **Sentence wording is unspecified but its stability is contractual**: the non-functional expectations make repeatable output part of the deliverable, so the exact phrasing of each of the eight sentence forms should be fixed in the specification rather than chosen during implementation.

- **Nested Data objects are unaddressed**: every AC uses a flat object. Properties of a nested Data object are published under a dotted path, while a condition reference inside that nested object is a bare sibling name. The story does not say what the sentence should print.

### Edge Cases

- **A conditional attribute silently cancels `#[Sometimes]` and `Optional` typing** — verified by execution. A property declared `string|Optional` with `#[RequiredIf]` loses the "only validated when included" sentence it would otherwise carry. Matters because the consumer loses real information as a side effect of this story's change.

- **`#[Present]` cancels every requiring rule, including a sibling `#[RequiredIf]` on the same property** — verified. A property carrying both publishes no requirement at all at runtime. Matters because a naive per-attribute implementation would emit a conditional-requirement sentence that the API does not enforce.

- **A conditional attribute on a property that also has a default value**: STORY-001-000's recorded decision publishes such a property as optional, which is right, but the condition sentence "required when X is Y" is then misleading — the consumer may always omit it. Matters for AC1's exact wording.

- **Multiple compared values on one attribute**: both `#[RequiredIf]` and `#[RequiredUnless]` are variadic and flatten their values. The ACs only show the single-value case.

- **`#[RequiredUnless]` accepts a null value** in its signature, which has no obvious rendering as prose.

- **A `FieldReference` marked as resolving from the payload root**, rather than relative to the enclosing object, changes which field the sentence should name.

- **The same attribute class declared twice on one property**: the registry holds one processor per class but the pipeline visits every instance, so sentences duplicate. Relevant to AC6's coherence requirement and noted as an open risk in the spike analysis.

- **A condition that references a hidden property**: the package supports marking properties hidden, and a condition sentence would name a field the consumer can neither see nor send, potentially exposing an internal name in a published document.

- **A condition that references a field on a different object** (a sibling of the parent, or a root-level discriminator): AC7 treats "field does not exist on that object" as malformed, but this is a legitimate polymorphic pattern and is the exact use case the story's business value section names.

- **A consumer who has already registered a third-party processor for one of these six classes**: the registry is last-write-wins with no reset, so the outcome depends on whether the consumer registers before or after the service provider boots.

### Technical Risks

- **The carve-out re-introduces package-owned precedence — the precise risk the spike warned against.** Mitigation direction: confine the package's judgement to *presentation* (what the published boolean says) and keep rule inference fully delegated, so the two authorities cannot drift on anything except a decision the package has deliberately taken; and pin the boundary with a test that fails loudly if the upstream marker starts distinguishing conditional rules itself.

- **Widened coupling to Laravel Data's support namespace.** Requirement derivation is currently documented as the sole holder of that coupling. Condition sentences need `FieldReference` and per-attribute parameter access, which spreads the coupling into the processor layer. Mitigation direction: keep the reference-to-name conversion in the one existing base class that already does it rather than repeating it per processor.

- **Published contracts change for existing consumers.** Six attribute families flip from `required: true` to `required: false`, and `#[Present]` may flip the other way. Anyone committing generated OpenAPI will see a large diff. Mitigation direction: treat it as a documented behaviour correction with a changelog entry, and verify that properties carrying none of these attributes produce byte-identical output, as STORY-001-000 did.

- **Description assembly is spread across four writers and this story adds sentences to the busiest one.** AC6's stable-order requirement and the byte-identical-builds expectation both rest on it. Mitigation direction: pin the complete sentence sequence for a multi-attribute property in a test rather than asserting on substrings.

- **The singleton registry gains further permanent entries** with no reset hook, which the spike already flagged as interacting badly with test isolation.

- **Descriptions carry HTML markup** (`<code>` wrappers) by existing convention. Condition sentences naming fields and values will inherit that choice, and it must remain acceptable to both named downstream consumers.

- **Determinism depends on attribute enumeration order**, which is PHP reflection's source order — verified stable in this exploration, but it is an implicit dependency worth stating rather than assuming.

### Acceptance Criteria Coverage

| AC# | Description | Addressable? | Gaps/Notes |
|-----|-------------|--------------|------------|
| 1 | Value-conditional requirement documented as optional with its condition stated | Partial | The sentence half is straightforward. The "listed as optional" half requires changing requirement derivation, not adding a processor — currently publishes `required: true`. Wording is also under-determined when the property has a default value. |
| 2 | Inverted value-conditional requirement documented | Partial | Same carve-out dependency as AC1. Additionally, `#[RequiredUnless]` permits a null compared value, which has no stated rendering. |
| 3 | Requirement conditional on presence of another field documented | Partial | Same carve-out dependency. Multi-field rendering ("both … and …") needs a fixed phrasing; field naming may not match published parameter names for nested objects. |
| 4 | Requirement conditional on absence of another field documented | Partial | Same carve-out dependency. "Neither … nor …" phrasing needs fixing; AC covers two fields but the attribute is variadic. |
| 5 | Presence-only requirements distinguished from value requirements | Partial | Descriptions are straightforward and need no carve-out. But the AC does not state requirement status, and `#[Present]` currently publishes `required: false` while the API demands inclusion — leaving it unaddressed produces a self-contradictory entry. Needs the open decision recorded. |
| 6 | Several requirement attributes on one property produce one coherent description | Partial | Blocked on two ambiguities: the requirement status for `#[RequiredIf]` + `#[Nullable]` is contradictory between AC1 and STORY-001-000, and the "null value is accepted" sentence does not exist in the package today. Stable ordering itself is achievable — attribute enumeration order is source order and was verified repeatable. |
| 7 | A malformed or unrecognised requirement attribute does not break the build | Yes | Satisfied trivially if references are rendered verbatim, since nothing is looked up. Should still be pinned by a test, and the AC's framing should be revisited — a reference to a field on another object is a legitimate polymorphic pattern, not a malformed declaration. |

Seven ACs assessed; one fully addressable as written, six partial pending the decisions recorded above.

---

## Empirical Verification

Run with `./jig pest` (PHP 8.4, Orchestra Testbench) in an isolated worktree at commit `1378504`, against throwaway Data classes exercising every attribute in the story's scope. The probes were removed after running; this story should add permanent equivalents.

Still valid at `69ed46c`: `git diff 1378504..69ed46c -- src/` is empty. The three intervening commits changed the pipeline canvas, `RequiredStageTest` and `RequirementReconciliationTest` only, so no result below has been invalidated. Suite at `69ed46c`: 374 passed, 1 skipped.

### Result 1 — every conditional attribute is currently published as unconditionally required ✅

Pipeline output today, for properties whose declared type admits null:

| Declaration | Published contract says | The API actually enforces |
| --- | --- | --- |
| `#[RequiredIf('accountType','business')] public ?string $companyName` | `required: true` | `nullable, string, required_if:accountType,business` |
| `#[RequiredUnless('status','approved')] public ?string $reason` | `required: true` | `nullable, string, required_unless:status,approved` |
| `#[RequiredWith('cardNumber')] public ?string $cardCvc` | `required: true` | `nullable, string, required_with:cardNumber` |
| `#[RequiredWithAll([...])] public ?string $shippingCity` | `required: true` | `nullable, string, required_with_all:…` |
| `#[RequiredWithout('email')] public ?string $phone` | `required: true` | `nullable, string, required_without:email` |
| `#[RequiredWithoutAll([...])] public ?string $fallbackContact` | `required: true` | `nullable, string, required_without_all:…` |

No description mentions any condition; every one reads `"Must be a string."` This is a live defect on the current branch, introduced by STORY-001-000 and covered by no story other than this one.

### Result 2 — `#[Present]` publishes the opposite error ✅

`#[Present] public string $terms` publishes `required: false`, while the enforced rules are `string, present` — the key must appear in the request. Upstream, `AttributesRuleInferrer` removes every requiring rule when it encounters `Present`, so the requirement derivation sees no requiring rule and answers "optional". A description sentence alone would leave `required: false` beside the words "must be included in the request".

`#[Filled] public string $title` enforces `required, string, filled` and publishes `required: true`, which is correct; only its description is missing.

### Result 3 — a conditional attribute cancels `Sometimes` ✅

`AttributesRuleInferrer` removes `Sometimes` whenever it adds a requiring rule. Verified: `string|Optional` with `#[RequiredIf]` publishes `onlyValidatedWhenPresent: false` and loses the "Only validated when included in the request." sentence, and `#[Sometimes]` + `#[RequiredIf]` on the same property enforces `string, required_if:…` with no `sometimes` rule at all.

### Result 4 — `#[Present]` cancels a sibling conditional attribute ✅

A property carrying `#[Filled] #[Present] #[RequiredIf(...)]` enforces `nullable, string, filled, present, required_if:…` at runtime — but the requiring rule is stripped from the inferred set, so the property is not conditionally required. A per-attribute implementation that emitted one sentence per attribute would publish a condition the API does not enforce.

### Result 5 — attribute enumeration order is source order and is repeatable ✅

For a property declared `#[Filled] #[Present] #[RequiredIf(...)]`, the pipeline's enumeration returned `Filled, Present, RequiredIf` — declaration order, stable across runs. AC6's stable-ordering requirement is achievable through the existing description accumulator without new machinery.

### Result 6 — a dangling field reference is already harmless ✅

`#[RequiredIf('doesNotExist','whatever')]` generated documentation without error. AC7 is satisfied by construction as long as the implementation does not attempt to resolve references against sibling properties — which is an argument for rendering them verbatim.

### Correction this forces on the story

The background section states that a conditionally required property "appears with no indication that it is ever required". On the current branch it appears as **unconditionally required**, which misleads consumers in the opposite direction and is strictly worse than the gap the story was written against. The story should be re-framed as a defect correction, its scope should explicitly include the requirement-status carve-out in the pipeline, and its cross-canvas reach and 2-day estimate should be revisited.
