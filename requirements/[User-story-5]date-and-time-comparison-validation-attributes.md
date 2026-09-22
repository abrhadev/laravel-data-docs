# [STORY-001-005] Date and Time Comparison Attribute Documentation

**Estimated effort**: 3 days

### Background

Date fields carry constraints that plain type information cannot express: a booking start must fall after today, an end date must fall after the start date, a birth date must fall before a fixed cut-off. Laravel Data expresses these through `#[After]`, `#[AfterOrEqual]`, `#[Before]`, `#[BeforeOrEqual]`, and `#[DateEquals]`, plus `#[TimeZone]` for time-zone identifier fields.

The package already documents `#[Date]` and `#[DateFormat]`, so a consumer learns what shape a date must take, but learns nothing about the range it must fall in. Date-range rules are also the family most often expressed relative to *another field*, which makes them impossible to guess: nothing in the current documentation reveals that `ends_at` must follow `starts_at`.

Key points:

- **Business value and user needs**: Booking, scheduling, subscription, and reporting endpoints are all governed by date-range rules, and none of those rules are currently published.
- **Relationship with other features**: Builds on the same date dimension as the already-supported `#[Date]` and `#[DateFormat]` attributes, which are unchanged by this story.
- **Why this capability is needed now**: Date-range rejections are a common and confusing class of integration failure, because the consumer's value looks well-formed and is still refused.

### Business Value

- Provide **API consumers** with the acceptable range for every date field, so that scheduling and booking requests succeed on the first attempt.
- Support **relative date rules**, where one date field is bounded by another, by naming the referenced field in the published contract.
- Enable **support teams** to explain date rejections from the documentation alone.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties. Date properties are already documented with their expected format where `#[Date]` or `#[DateFormat]` is present.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Output must remain valid for both.
- **Business constraints**: The package documents behaviour and must not change it. Relative bounds such as `today` are evaluated when the request is handled, not when documentation is built, so the documentation must describe the rule rather than resolve it to a fixed date.

### Scope In

- Documenting the date bound attributes `#[After]`, `#[AfterOrEqual]`, `#[Before]`, `#[BeforeOrEqual]`, and `#[DateEquals]`.
- Distinguishing, in the generated description, a bound given as a literal or relative date from a bound given as a reference to another field.
- Documenting `#[TimeZone]` as a statement that the field must hold a valid time-zone identifier.

### Scope Out

- `#[Date]` and `#[DateFormat]`, which are already supported today and are not re-specified here.
- Numeric bound attributes such as `#[Min]`, `#[Max]`, and `#[Between]`, which are already supported.
- Conditional requirement and prohibition attributes — covered by STORY-001-001 and STORY-001-002.
- Resolving a relative bound such as `today` to a concrete calendar date in the generated documentation.
- Any change to runtime validation behaviour.

### Acceptance Criteria

#### AC1: A literal date bound is documented

**Given** an endpoint accepts a Data object with a property `published_at` annotated `#[After('2026-01-01')]`
**When** documentation is generated for that endpoint
**Then** the description of `published_at` states that the value must be a date after `2026-01-01`

#### AC2: An inclusive literal date bound is documented distinguishably

**Given** a property `valid_from` annotated `#[AfterOrEqual('2026-01-01')]`
**When** documentation is generated
**Then** the description of `valid_from` states that the value must be a date on or after `2026-01-01`
**And** the wording is distinguishable from the exclusive bound wording used for `#[After]`

#### AC3: Upper date bounds are documented in both exclusive and inclusive forms

**Given** a property `birth_date` annotated `#[Before('2010-01-01')]`
**And** a property `valid_until` annotated `#[BeforeOrEqual('2026-12-31')]`
**When** documentation is generated
**Then** the description of `birth_date` states that the value must be a date before `2010-01-01`
**And** the description of `valid_until` states that the value must be a date on or before `2026-12-31`

#### AC4: A bound expressed against another field names that field

**Given** a property `ends_at` annotated `#[After('starts_at')]`
**When** documentation is generated
**Then** the description of `ends_at` states that the value must be a date after the value submitted in `starts_at`
**And** the wording makes clear that the bound is another request field, not a literal date

#### AC5: A relative bound is documented as written, not resolved

**Given** a property `scheduled_for` annotated `#[After('today')]`
**When** documentation is generated on 2026-09-22
**Then** the description of `scheduled_for` states that the value must be a date after `today`
**And** the description does not contain the literal date `2026-09-22`

#### AC6: An exact date match requirement is documented

**Given** a property `settlement_date` annotated `#[DateEquals('2026-06-30')]`
**When** documentation is generated
**Then** the description of `settlement_date` states that the value must be the date `2026-06-30`

#### AC7: A time-zone field is documented

**Given** a property `timezone` annotated `#[TimeZone]`
**When** documentation is generated
**Then** the description of `timezone` states that the value must be a valid time-zone identifier
**And** the description includes at least one concrete example such as `Europe/Berlin`

#### AC8: A pair of bounds on one property produces a single range statement

**Given** a property `event_date` annotated with both `#[AfterOrEqual('2026-01-01')]` and `#[BeforeOrEqual('2026-12-31')]`
**When** documentation is generated
**Then** the description of `event_date` conveys both the lower and the upper bound
**And** the two bounds appear in a stable, repeatable order across successive documentation builds

#### AC9: A date bound combines with the already-supported format attributes

**Given** a property `starts_at` annotated with both `#[DateFormat('Y-m-d')]` and `#[After('today')]`
**When** documentation is generated
**Then** the existing format sentence remains present in the description
**And** the new bound sentence is present alongside it

#### AC10: A malformed or unrecognised attribute does not break the build

**Given** a property carries a date bound attribute whose bound value is neither a parseable date nor the name of a field on that Data object
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- A relative bound must never be resolved to a fixed date at documentation build time, so that published documentation does not silently become wrong the day after it is generated.
- Repeated documentation builds over an unchanged codebase produce identical output.
