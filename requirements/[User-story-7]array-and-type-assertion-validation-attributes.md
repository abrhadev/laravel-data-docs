# [STORY-001-007] Array and Type-Assertion Attribute Documentation

**Estimated effort**: 3 days

### Background

Laravel Data offers a group of attributes that assert the shape of a value rather than its content: `#[ArrayType]`, `#[ListType]`, `#[Distinct]`, and `#[RequiredArrayKeys]` for collections, and `#[BooleanType]`, `#[IntegerType]`, `#[StringType]`, and `#[Numeric]` for scalars, alongside the digit-count constraints `#[MinDigits]` and `#[MaxDigits]`.

The scalar assertions often duplicate what the property's PHP type already tells the generator, but not always — a property typed as `mixed`, or one where the attribute deliberately narrows the accepted input, carries information the type alone does not. The collection attributes are more valuable still and are entirely absent today: nothing in the generated contract distinguishes a sequential list from a keyed map, states that entries must be unique, or names the keys an array entry must contain. For endpoints that accept bulk payloads this is the difference between a usable contract and a guess.

Key points:

- **Business value and user needs**: Bulk and collection endpoints are currently documented as opaque arrays, which forces consumers to reverse-engineer the payload shape.
- **Relationship with other features**: Complements the existing numeric and string constraint coverage by describing the container rather than the value.
- **Why this capability is needed now**: Collection payload shape is a frequent source of integration back-and-forth, and the scalar assertions are cheap to add in the same pass.

### Business Value

- Provide **API consumers** with the shape of collection fields — list versus keyed map, required entry keys, uniqueness — so that bulk payloads can be constructed correctly.
- Support **accurate scalar typing** in the published contract for properties whose PHP type is broader than the values the API actually accepts.
- Enable **client code generators** to emit correctly typed models for collection and loosely typed fields.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Reaches beyond the attribute-translation layer**: some collection constraints need fields the parameter record does not yet carry, so this story also touches the documentation-records area. Allow for that in planning.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties, including a published type derived from each property's PHP type.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Output must remain valid for both.
- **Business constraints**: The package documents behaviour and must not change it. Where a type assertion attribute and the property's PHP type disagree, the documentation must not silently contradict itself.

### Scope In

- Documenting the collection attributes `#[ArrayType]`, `#[ListType]`, `#[Distinct]`, and `#[RequiredArrayKeys]`.
- Documenting the scalar type assertions `#[BooleanType]`, `#[IntegerType]`, `#[StringType]`, and `#[Numeric]`, including the case where the assertion narrows a broader PHP type.
- Documenting the digit-count constraints `#[MinDigits]` and `#[MaxDigits]`.
- Settling whether type-assertion attributes participate in the same reconciliation STORY-001-000 establishes for requirement attributes, since both derive from the declared type.

### Scope Out

- `#[Digits]` and `#[DigitsBetween]`, which are already supported today and are not re-specified here.
- Documenting the structure of nested Data objects, which the package already derives from property types.
- Conditional requirement, prohibition, and comparison attributes — covered by STORY-001-001 through STORY-001-003.
- Any change to runtime validation behaviour.

### Acceptance Criteria

#### AC1: An array-typed property is published as an array

**Given** an endpoint accepts a Data object with a property `metadata` typed `mixed` and annotated `#[ArrayType]`
**When** documentation is generated for that endpoint
**Then** `metadata` is published as an array in the generated contract

#### AC2: A sequential list is distinguished from a keyed map

**Given** a property `tags` annotated `#[ListType]`
**When** documentation is generated
**Then** the description of `tags` states that the value must be a sequential list with consecutive numeric keys starting at zero

#### AC3: A uniqueness constraint on entries is documented

**Given** a property `recipient_ids` annotated `#[Distinct]`
**When** documentation is generated
**Then** the description of `recipient_ids` states that its entries must be unique

#### AC4: Required keys of an array value are documented

**Given** a property `address` annotated `#[RequiredArrayKeys(['street', 'city', 'country'])]`
**When** documentation is generated
**Then** the description of `address` states that the value must contain the keys `street`, `city`, and `country`

#### AC5: A scalar type assertion narrowing a broader PHP type is published

**Given** a property `quantity` typed `mixed` and annotated `#[IntegerType]`
**When** documentation is generated
**Then** `quantity` is published as an integer in the generated contract

#### AC6: A scalar type assertion matching the PHP type does not duplicate information

**Given** a property `title` typed `string` and annotated `#[StringType]`
**When** documentation is generated
**Then** `title` is published as a string
**And** the description does not repeat the type a second time in prose

#### AC7: Boolean and numeric assertions are published

**Given** a property `is_active` typed `mixed` and annotated `#[BooleanType]`
**And** a property `rate` typed `mixed` and annotated `#[Numeric]`
**When** documentation is generated
**Then** `is_active` is published as a boolean
**And** `rate` is published as a number

#### AC8: Digit-count constraints are documented

**Given** a property `pin` annotated `#[MinDigits(4)]` and `#[MaxDigits(6)]`
**When** documentation is generated
**Then** the description of `pin` states that the value must have between `4` and `6` digits

#### AC9: Collection attributes combine into one coherent description

**Given** a property `recipient_ids` annotated with `#[ListType]`, `#[Distinct]`, and `#[Min(1)]`
**When** documentation is generated
**Then** the description contains the sequential-list statement, the uniqueness statement, and the existing minimum-entries statement
**And** the statements appear in a stable, repeatable order across successive documentation builds

#### AC10: A type assertion that contradicts the property's declared type does not produce contradictory documentation

**Given** a property `code` typed `string` and annotated `#[IntegerType]`
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** the published contract for `code` states a single type rather than two conflicting ones
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- Where a type assertion adds nothing beyond the property's declared type, the generated documentation must not become noisier — the existing output for those properties stays as readable as it is today.
- Repeated documentation builds over an unchanged codebase produce identical output.
