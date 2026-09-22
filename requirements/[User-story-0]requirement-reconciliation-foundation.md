# [STORY-001-000] Requirement Status Reconciliation Foundation

**Estimated effort**: 2 days
**Prerequisite for**: STORY-001-001. Independent of STORY-001-002 through STORY-001-008.

### Background

The published contract answers "must I send this field?" from the property's declared type alone — whether the type admits null, whether it is optional, whether a default exists. For properties that express their requirement through the type, that answer is correct and is not in question.

It is wrong whenever a validation attribute says something different. The following is reproducible against the package today, verified by running the documentation pipeline over a sample Data class:

| Declaration | Published contract says | The API actually enforces |
| --- | --- | --- |
| `#[Required] public ?string $email` | optional | must be present, may be null |
| `#[Nullable] public string $middle_name` | required, rejects null | must be present, may be null |
| `#[Sometimes] public string $coupon_code` | required | only validated when included |

The `email` row is the damaging one: the documentation tells consumers a mandatory field is optional, so a request built strictly from the published contract is rejected.

There is a second, structural reason this cannot be fixed by describing the attributes one at a time. Requirement status is computed at a later point in the documentation pipeline than attribute interpretation, and it overwrites whatever was determined earlier. Any per-attribute treatment of `#[Required]`, `#[Nullable]`, or `#[Sometimes]` would therefore be silently discarded. The correction has to happen where requirement status is decided.

Key points:

- **Business value and user needs**: A wrong requirement status is worse than a missing one, because consumers act on it with confidence.
- **Relationship with other features**: This story establishes the single authority on requirement status. STORY-001-001 layers conditional requirement rules on top of it and cannot be correct without it. The remaining six family stories are unaffected.
- **Why this capability is needed now**: It is a live defect, not a gap — the package currently publishes statements that contradict the API.

### Business Value

- Provide **API consumers** with a requirement status that matches what the API enforces, removing the class of integration failure where a request built exactly to the documentation is rejected.
- Support **every later requirement-related story** by establishing one authority on requirement status rather than a second, independently maintained one that can drift from runtime behaviour.
- Enable **the team** to add conditional requirement documentation without first having to settle precedence questions story by story.

### Dependencies and Assumptions

- **Prerequisites**: None.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties.
- **Integration points**: Laravel Data itself already reconciles type-derived and attribute-derived requirement semantics for runtime validation. That reconciliation is available without a request payload, which has been confirmed by execution. This story inherits that behaviour rather than restating it.
- **Business constraints**: The package documents behaviour and must not change it. Documentation generation must not require a request payload or a database connection.

### Scope In

- Determining a property's published requirement status from its declared type **and** its requirement attributes together, under the same precedence the API enforces at runtime.
- Correcting the pipeline so that requirement status determined from attributes is not overwritten later in the same run.
- Adding a regression test that pins the reconciled behaviour, so an upgrade of the underlying validation library that changes precedence fails loudly rather than silently altering published documentation.

### Scope Out

- Conditional requirement attributes such as `#[RequiredIf]` and `#[RequiredWith]` — covered by STORY-001-001.
- Prohibition, exclusion, comparison, enumeration, date, file, array, and database-backed attributes — covered by STORY-001-002 through STORY-001-008.
- Changing the requirement status of any property that carries no requirement attribute.
- Any change to runtime validation behaviour.

### Open Decision

One case has no obviously correct answer and must be settled before implementation:

**A property that carries both a default value and `#[Required]`.** The validation rules for such a property include a requirement, but a consumer who omits the field receives no error, because the default applies. Publishing it as required is faithful to the declared rules; publishing it as optional is faithful to observable API behaviour. The team must pick one, and AC6 records whichever is chosen. This story should not ship with the two readings left open.

### Acceptance Criteria

#### AC1: A requirement attribute overrides a type that admits null

**Given** an endpoint accepts a Data object with a property `email` whose declared type admits null, annotated `#[Required]`
**When** documentation is generated for that endpoint
**Then** `email` is listed as required in the published contract
**And** it is documented as accepting a null value

#### AC2: A nullable attribute does not make a property optional

**Given** a property `middle_name` whose declared type does not admit null, annotated `#[Nullable]`
**When** documentation is generated
**Then** `middle_name` is documented as accepting a null value
**And** it remains listed as required, because the field must still be included in the request

#### AC3: A conditionally validated property is published as optional

**Given** a property `coupon_code` whose declared type does not admit null and which has no default value, annotated `#[Sometimes]`
**When** documentation is generated
**Then** `coupon_code` is listed as optional
**And** its description states that it is validated only when it is included in the request

#### AC4: Attribute-derived requirement status survives the whole generation run

**Given** any property whose requirement attribute disagrees with its declared type
**When** documentation is generated
**Then** the published requirement status is the reconciled one
**And** it is not replaced by a status derived from the declared type alone at any later point in generation

#### AC5: A property with no requirement attribute is unchanged

**Given** a property `nickname` whose declared type admits null and which carries no requirement attribute
**And** a property `title` whose declared type does not admit null and which carries no requirement attribute
**When** documentation is generated
**Then** `nickname` is listed as optional and documented as accepting a null value
**And** `title` is listed as required
**And** both match the output the package produces today

#### AC6: A property with both a default value and a requirement attribute follows the recorded decision

**Given** a property `status` that declares a default value of `draft`, annotated `#[Required]`
**When** documentation is generated
**Then** `status` is published with the requirement status chosen in the Open Decision above
**And** the same choice is applied to every property in this situation

#### AC7: The reconciled behaviour is protected against upstream change

**Given** the underlying validation library changes the precedence it applies between declared types and requirement attributes
**When** the test suite is run
**Then** at least one test fails and identifies the changed behaviour
**And** the failure occurs before any documentation is published

### Non-Functional Expectations

- Reconciliation must work without a request payload and without a database connection, so documentation can be generated in continuous integration.
- Repeated documentation builds over an unchanged codebase produce identical output.
- Properties that carry no requirement attribute must produce byte-identical documentation to today's output, so this story can be verified as a pure correction with no incidental change.
