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
- Making the examples of `#[Same]` and `#[InArray]` fields match their referenced field's example — this needs a pass across all properties and is a follow-up.

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

**Given** a property `primary_tag` annotated `#[InArray('tags.*')]`
**When** documentation is generated
**Then** the description of `primary_tag` states that its value must be one of the values submitted in `tags`

> Only the wildcard form `tags.*` checks membership of an array field. A bare `#[InArray('tags')]` is compared against the key `tags` itself and rejects every value when `tags` is an array, so it is documented as it behaves: its value must equal the value of `tags`.

#### AC4: A confirmation rule surfaces the companion field

**Given** a property `password` annotated `#[Confirmed]`
**When** documentation is generated for that endpoint
**Then** the published contract includes a parameter named `password_confirmation` in addition to `password`
**And** the description of `password` states that a matching `password_confirmation` value must be sent

#### AC5: A confirmation rule documents the companion field that is actually enforced

**Given** a property `email` annotated `#[Confirmed('email_repeat')]`
**When** documentation is generated
**Then** the published contract includes a parameter named `email_confirmation`, and no parameter named `email_repeat`
**And** the description of `email` states that a matching `email_confirmation` value must be sent

> Laravel Data's `#[Confirmed]` accepts no arguments. On PHP 8.4, PHP silently discards `'email_repeat'`, and at runtime the API requires `email_confirmation`. On PHP 8.3, PHP rejects the declaration when the attribute is read, so neither the application nor the documentation can use that Data object; AC5 applies to PHP 8.4 and later. The documentation follows what is enforced. If a future Laravel Data release passes a custom name through to validation, the documented name must follow it.

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

#### AC11: The companion field's requirement follows its source field

**Given** a property `password` annotated `#[Confirmed]` that is required
**And** a property `recovery_pin` annotated `#[Confirmed]` that is optional
**When** documentation is generated
**Then** `password_confirmation` is published as required
**And** `recovery_pin_confirmation` is published as optional, with a description stating that it is required when `recovery_pin` is sent
**And** each companion field is published as nullable exactly when its source field is

#### AC12: Published examples satisfy the documented acceptance and confirmation rules

**Given** a property `terms_accepted` annotated `#[Accepted]`
**And** a property `data_sharing` annotated `#[Declined]`
**And** a property `password` annotated `#[Confirmed]`
**When** documentation is generated
**Then** the example for `terms_accepted` is one of the values that count as acceptance
**And** the example for `data_sharing` is one of the values that count as refusal
**And** the example for `password_confirmation` is identical to the example for `password`

### Non-Functional Expectations

- A confirmation field introduced by `#[Confirmed]` must be presented consistently with ordinary documented parameters, so that consumers and code generators treat it as a real part of the request contract.
- Repeated documentation builds over an unchanged codebase produce identical output.
