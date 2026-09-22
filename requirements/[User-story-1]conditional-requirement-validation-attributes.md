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

#### AC6: Several requirement attributes on one property produce one coherent description

**Given** a property `vat_number` annotated with both `#[RequiredIf('account_type', 'business')]` and `#[Nullable]`
**When** documentation is generated
**Then** `vat_number` carries the requirement status established by STORY-001-000
**And** its description contains both the conditional requirement sentence and the statement that a null value is accepted
**And** the two sentences appear in a stable, repeatable order across successive documentation builds

#### AC7: A malformed or unrecognised requirement attribute does not break the build

**Given** a Data object property carries a conditional requirement attribute whose referenced field does not exist on that object
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- Generated condition sentences must be understandable by an API consumer who has never seen the application's source code — they name fields by the same names that appear in the documented request payload.
- Repeated documentation builds over an unchanged codebase produce identical output, so that generated API reference files can be committed and diffed meaningfully.
