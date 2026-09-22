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
