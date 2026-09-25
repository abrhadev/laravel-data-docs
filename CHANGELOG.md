# Changelog

All notable changes to `laravel-data-docs` will be documented in this file.

## Unreleased

### Added

- Conditional requirement attributes are documented. `#[RequiredIf]`, `#[RequiredUnless]`, `#[RequiredWith]`, `#[RequiredWithAll]`, `#[RequiredWithout]` and `#[RequiredWithoutAll]` add a sentence naming the field and value the condition depends on, for example "Required when <b><i>account_type</i></b> is <code>business</code>.", and such a property is published as optional. A condition Laravel Data does not enforce is not published: one followed by `#[Present]`, `#[Required]`, another conditional attribute or an equivalent `#[Rule(...)]`, or one with no compared value.
- `#[Filled]` adds "When included, must not be empty."; `#[Present]` adds "Must be included in the request, but may be empty." on a nullable, uncast `string` property whose key must be sent and where nothing else rejects an empty value; with Laravel's default `ConvertEmptyStringsToNull`, an empty value on any other type is rejected.
- Nullable properties add "A null value is accepted."
- Prohibition attributes are documented. `#[Prohibited]`, `#[ProhibitedIf]` and `#[ProhibitedUnless]` add a sentence stating when the field must not be sent, for example "Must not be sent when <b><i>plan</i></b> is <code>enterprise</code>; the request is rejected if it is.", and `#[Prohibits]` states which other fields sending it forbids. Requirement status is unchanged: a prohibited property that is not nullable, not `Optional` and has no default is still published as required, because Laravel Data still infers `required` for it.
- Exclusion attributes are documented. `#[Exclude]`, `#[ExcludeIf]`, `#[ExcludeUnless]`, `#[ExcludeWith]` and `#[ExcludeWithout]` add a sentence such as "Not validated, and removed from the validated input when <b><i>order_type</i></b> is <code>internal</code>." The field stays documented. The sentence deliberately does not say the value is ignored: Laravel Data builds the Data object from the request payload, so an excluded value still reaches it, unvalidated; only the validator's validated output drops it.
- A property that is required, rejects an empty value and is unconditionally `#[Prohibited]` adds "Note: this field is both required and prohibited, so no request can pass validation." It is not added when an exclusion attribute is declared before `#[Prohibited]`, because Laravel stops validating an excluded field and such a request passes.

### Changed

- `nullable` is published only where a null value passes validation and the property's PHP type admits it. `#[Required] ?string`, `#[Filled] ?string`, `#[Accepted] ?bool` and `#[Declined] ?bool` now publish `nullable: false`, because Laravel rejects null for each.
- `#[Present]` properties are published as required, because validation fails when the key is omitted. Previously `#[Present]` did not make a property required, so `#[Present] ?string` was published `required: false`. As with any requirement, `Sometimes`, an `Optional` type or a default value still makes the property optional.
- `#[Accepted]` and `#[Declined]` properties are published as required, because validation fails when the key is omitted.
- Field names in descriptions are rendered as `<b><i>field</i></b>` and values as `<code>value</code>`, including in the comparison processors' sentences for field references such as `#[GreaterThan(new FieldReference('min_price'))]`.
- A backed enum is rendered by its case name and backing value wherever it appears in a description, matching the "Must be one of" enum list: `#[RequiredIf('account_type', AccountType::Business)]` now reads "Required when <b><i>account_type</i></b> is <code>Business</code> (business).", and a backed enum default reads "Defaults to <code>Active</code> (active)." This applies to `#[RequiredIf]`, `#[RequiredUnless]`, `#[ProhibitedIf]`, `#[ProhibitedUnless]`, `#[ExcludeIf]`, `#[ExcludeUnless]` and default values. Previously a condition showed only the backing value and a default only the case name. The schema `default` is unchanged and still holds the backing value.

### Fixed

- A boolean compared value in a `#[RequiredIf]` or `#[RequiredUnless]` sentence is rendered as `true` or `false`. Previously `false` rendered as an empty code span and `true` as `1`.
