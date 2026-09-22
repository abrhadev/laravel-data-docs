# [STORY-001-008] Database-Backed Constraint Attribute Documentation

**Estimated effort**: 2 days

### Background

A final group of validation attributes constrains a value against stored data rather than against a literal: `#[Unique]` asserts that no record already holds the value, `#[Exists]` asserts that a record does, and `#[CurrentPassword]` asserts that the value matches the authenticated user's password. The formatting attribute `#[MacAddress]` completes the uncovered set and is grouped here because it is a single small addition to the already-supported pattern family.

None of these are published today. Their absence produces the most opaque rejections in the whole set, because the consumer's value is well-formed and would be accepted at any other moment — the request fails purely because of the state of the system. A registration endpoint that rejects an already-taken email, with nothing in the contract to say it might, is a support ticket every time.

These attributes need careful handling: they name database tables and columns, which are internal implementation details that must not leak into a public API contract. The documentation should state the *business* constraint, not the storage location.

Key points:

- **Business value and user needs**: Consumers need to anticipate state-dependent rejections and handle them, rather than treating them as unexpected errors.
- **Relationship with other features**: Independent of every other story in this decomposition; it is the smallest and can be used to close out the feature.
- **Why this capability is needed now**: Uniqueness and existence rejections are common on the highest-traffic endpoints — registration, invitation, and resource linking.

### Business Value

- Provide **API consumers** with advance warning of state-dependent rejections, such as an already-registered email address, so that client applications handle them as an expected outcome rather than a failure.
- Support **safe public documentation** by stating these constraints in business terms, without exposing internal table or column names.
- Enable **support teams** to explain "that value is already taken" and "that record does not exist" rejections from the published contract.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. The documentation build must not require a database connection.
- **Business constraints**: Generated documentation may be published externally, so it must never disclose database table or column names. The package documents behaviour and must not change it.

### Scope In

- Documenting `#[Unique]` and `#[Exists]` as business-level statements of a state-dependent constraint, with internal storage names withheld.
- Documenting `#[CurrentPassword]` as a statement that the value must match the authenticated user's current password.
- Documenting `#[MacAddress]` as a format constraint, consistent with the package's existing format attribute coverage.

### Scope Out

- Querying the database at documentation build time for any purpose.
- Publishing table names, column names, connection names, or any other storage detail.
- Documenting the scoping and exception conditions of a `#[Unique]` rule, such as which record is ignored during an update.
- Every other attribute family in this decomposition — covered by STORY-001-001 through STORY-001-007.
- Any change to runtime validation behaviour.

### Acceptance Criteria

#### AC1: A uniqueness constraint is documented in business terms

**Given** an endpoint accepts a Data object with a property `email` annotated `#[Unique('users', 'email')]`
**When** documentation is generated for that endpoint
**Then** the description of `email` states that the value must not already be in use
**And** the description contains neither the table name `users` nor the column name `email` as storage references

#### AC2: An existence constraint is documented in business terms

**Given** a property `project_id` annotated `#[Exists('projects', 'id')]`
**When** documentation is generated
**Then** the description of `project_id` states that the value must refer to an existing record
**And** the description does not contain the table name `projects`

#### AC3: The current-password constraint is documented

**Given** a property `current_password` annotated `#[CurrentPassword]`
**When** documentation is generated
**Then** the description of `current_password` states that the value must match the authenticated user's current password

#### AC4: A MAC address constraint is documented as a format

**Given** a property `device_mac` annotated `#[MacAddress]`
**When** documentation is generated
**Then** the description of `device_mac` states that the value must be a valid MAC address
**And** the description includes a concrete example such as `00:1B:44:11:3A:B7`

#### AC5: State-dependent rejection is described as an expected outcome

**Given** a property `email` annotated `#[Unique('users', 'email')]` and `#[Required]`
**When** documentation is generated
**Then** the description makes clear that a well-formed value may still be rejected because it is already in use, producing a `422` response

#### AC6: Documentation generates without a database connection

**Given** an endpoint whose Data object uses `#[Unique]` and `#[Exists]` attributes
**And** no database connection is available in the environment where documentation is generated
**When** documentation is generated
**Then** documentation generation completes successfully
**And** the uniqueness and existence statements are present in the generated output

#### AC7: A database-backed attribute combines with already-supported attributes

**Given** a property `email` annotated with both `#[Email]` and `#[Unique('users', 'email')]`
**When** documentation is generated
**Then** the existing email-format sentence remains present in the description
**And** the uniqueness sentence is present alongside it
**And** the sentences appear in a stable, repeatable order across successive documentation builds

#### AC8: A malformed or unrecognised attribute does not break the build

**Given** a property carries a `#[Unique]` or `#[Exists]` attribute with no usable target
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- Generated documentation must be safe to publish externally: no storage identifier from these attributes may appear anywhere in the output.
- Documentation generation must never open a database connection, so that documentation can be built in environments that have no database — including continuous integration.
- Repeated documentation builds over an unchanged codebase produce identical output.
