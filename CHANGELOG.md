# Changelog

All notable changes to `laravel-data-docs` will be documented in this file.

## Unreleased

### Added

- Conditional requirement attributes are documented. `#[RequiredIf]`, `#[RequiredUnless]`, `#[RequiredWith]`, `#[RequiredWithAll]`, `#[RequiredWithout]` and `#[RequiredWithoutAll]` add a sentence naming the field and value the condition depends on, for example "Required when <b><i>account_type</i></b> is <code>business</code>.", and such a property is published as optional. A condition Laravel Data does not enforce is not published: one followed by `#[Present]`, `#[Required]`, another conditional attribute or an equivalent `#[Rule(...)]`, or one with no compared value.
- `#[Filled]` adds "When included, must not be empty."; `#[Present]` adds "Must be included in the request, but may be empty." on a nullable, uncast `string` property whose key must be sent and where nothing else rejects an empty value; with Laravel's default `ConvertEmptyStringsToNull`, an empty value on any other type is rejected.
- Nullable properties add "A null value is accepted."

### Changed

- `nullable` is published only where a null value passes validation and the property's PHP type admits it. `#[Required] ?string`, `#[Filled] ?string`, `#[Accepted] ?bool` and `#[Declined] ?bool` now publish `nullable: false`, because Laravel rejects null for each.
- `#[Present]` properties are published as required, because validation fails when the key is omitted. Previously `#[Present]` did not make a property required, so `#[Present] ?string` was published `required: false`. As with any requirement, `Sometimes`, an `Optional` type or a default value still makes the property optional.
- `#[Accepted]` and `#[Declined]` properties are published as required, because validation fails when the key is omitted.
- Field names in descriptions are rendered as `<b><i>field</i></b>` and values as `<code>value</code>`, including in the comparison processors' sentences for field references such as `#[GreaterThan(new FieldReference('min_price'))]`.
