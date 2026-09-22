# [STORY-001-004] Enumerated Value and Negative Pattern Attribute Documentation

**Estimated effort**: 3 days

### Background

When a field accepts only a fixed set of values — an order status, a currency code, a role — the published contract should list those values. Laravel Data expresses this through `#[In]` and `#[Enum]`, and expresses the inverse through `#[NotIn]`, `#[NotRegex]`, `#[DoesntStartWith]`, and `#[DoesntEndWith]`.

None of these are reflected in the generated documentation. A status field constrained to three values is currently published as an unconstrained string, which forces the consumer to guess, and prevents client code generators and interactive documentation from offering the valid choices. This is the family with the largest gap between what the documentation format can express and what the package currently emits: allowed-value lists are a first-class concept in the published contract, so this information can be published as structured data rather than prose.

Key points:

- **Business value and user needs**: A list of allowed values is the single most useful piece of documentation for a constrained field, and the only one that downstream tooling can act on automatically.
- **Relationship with other features**: The package already documents string patterns and numeric bounds; allowed-value sets are the missing third dimension of value constraints.
- **Why this capability is needed now**: Enumerated fields are extremely common, and unlike most families in this decomposition, this one improves machine-readable output rather than only prose.

### Business Value

- Provide **API consumers** and **client code generators** with the exact set of accepted values for constrained fields, published as structured data rather than prose.
- Support **interactive API documentation**, where readers can select from valid values instead of typing a guess.
- Enable **developers maintaining the API** to keep the published list of allowed values in step with the code automatically, rather than through hand-written descriptions that drift.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Reaches beyond the attribute-translation layer**: publishing an allowed-value set needs a field the parameter record does not yet carry, so this story also touches the documentation-records area. Allow for that in planning.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties. Enum-typed properties reference PHP enums whose cases have stable backing values or names.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Allowed-value lists must be expressed in the form that OpenAPI tooling recognises.
- **Business constraints**: The package documents behaviour and must not change it.

### Scope In

- Publishing the allowed-value set from `#[In]` and `#[Enum]` as a structured list of values in the generated contract, not only as description text.
- Documenting the exclusion attributes `#[NotIn]`, `#[NotRegex]`, `#[DoesntStartWith]`, and `#[DoesntEndWith]` as readable statements of what is rejected.
- Reconciling an allowed-value set with an excluded-value set declared on the same property.
- Reconciling an attribute-declared allowed-value set with the set already derived from a property's PHP enum type, so that a single published list results rather than two competing ones.

### Scope Out

- Conditional requirement, prohibition, and cross-field comparison attributes — covered by STORY-001-001 through STORY-001-003.
- `#[Regex]`, `#[StartsWith]`, and `#[EndsWith]`, which are already supported today and are not re-specified here.
- Documenting enum cases used as a property's *response* type where no validation attribute is present.
- Any change to runtime validation behaviour.

### Acceptance Criteria

#### AC1: A fixed value list is published as a structured set of allowed values

**Given** an endpoint accepts a Data object with a property `status` annotated `#[In(['pending', 'shipped', 'cancelled'])]`
**When** documentation is generated for that endpoint
**Then** the published contract for `status` lists `pending`, `shipped`, and `cancelled` as its allowed values in a form that OpenAPI tooling recognises as an allowed-value set

#### AC2: An enum-backed constraint publishes the enum's values

**Given** a property `role` annotated `#[Enum(UserRole::class)]`, where `UserRole` is a backed enum with cases `admin`, `editor`, and `viewer`
**When** documentation is generated
**Then** the published contract for `role` lists `admin`, `editor`, and `viewer` as its allowed values

#### AC3: An enum with restricted cases publishes only the permitted cases

**Given** a property `role` annotated with an `#[Enum]` constraint on `UserRole` that permits only the `editor` and `viewer` cases
**When** documentation is generated
**Then** the published contract for `role` lists exactly `editor` and `viewer`
**And** `admin` does not appear among the allowed values

#### AC4: A forbidden value list is documented

**Given** a property `username` annotated `#[NotIn(['admin', 'root', 'system'])]`
**When** documentation is generated
**Then** the description of `username` states that `admin`, `root`, and `system` are not accepted
**And** no allowed-value set is published for `username`, since the field remains otherwise unconstrained

#### AC5: A forbidden pattern is documented

**Given** a property `slug` annotated `#[NotRegex('/^\\d/')]`
**When** documentation is generated
**Then** the description of `slug` states that values matching the pattern `/^\d/` are not accepted

#### AC6: Forbidden prefixes and suffixes are documented

**Given** a property `filename` annotated `#[DoesntStartWith(['.', '~'])]`
**And** a property `domain` annotated `#[DoesntEndWith(['.test', '.local'])]`
**When** documentation is generated
**Then** the description of `filename` states that it must not start with `.` or `~`
**And** the description of `domain` states that it must not end with `.test` or `.local`

#### AC7: An allowed set and a forbidden set on the same property are reconciled

**Given** a property `status` annotated with both `#[In(['pending', 'shipped', 'cancelled'])]` and `#[NotIn(['cancelled'])]`
**When** documentation is generated
**Then** the published allowed-value set for `status` contains `pending` and `shipped`
**And** `cancelled` does not appear among the allowed values

#### AC8: An allowed-value set combines with an already-supported attribute

**Given** a property `currency` annotated with both `#[In(['EUR', 'USD'])]` and `#[Uppercase]`
**When** documentation is generated
**Then** the published contract for `currency` lists `EUR` and `USD` as its allowed values
**And** the existing uppercase sentence remains present in the description

#### AC9: An empty or single-value list is handled without producing a misleading contract

**Given** a property `channel` annotated `#[In(['email'])]`
**When** documentation is generated
**Then** the published allowed-value set for `channel` contains exactly `email`

#### AC10: An attribute-declared set and a type-derived enum are reconciled into one published list

**Given** a property typed as the PHP enum `UserRole` with cases `admin`, `editor`, and `viewer`, additionally annotated `#[In(['editor', 'viewer'])]`
**When** documentation is generated
**Then** the published contract for `role` lists exactly one allowed-value set
**And** that set contains `editor` and `viewer` only

#### AC11: A malformed or unrecognised attribute does not break the build

**Given** a property carries an `#[Enum]` constraint referencing a class that is not a PHP enum
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- The generated contract must remain valid OpenAPI when an allowed-value set is published, so that existing client generators and documentation viewers continue to work unchanged.
- Repeated documentation builds over an unchanged codebase produce identical output, including the order of values in a published allowed-value set.
