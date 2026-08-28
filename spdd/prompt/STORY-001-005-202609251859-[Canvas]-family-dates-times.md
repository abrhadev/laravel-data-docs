# Date and Time Attributes

Family canvas for STORY-001-005 (`requirements/[User-story-5]date-and-time-comparison-validation-attributes.md`). Owns `DateFormatProcessor` and the registry rows `DateFormat` and `Date`.

**Story not shipped yet.** This canvas is reverse-codified from the current code only. STORY-001-005 extends it with `/spdd-prompt-update` (`After`, `AfterOrEqual`, `Before`, `BeforeOrEqual`, `DateEquals`, `TimeZone`); nothing that story adds is specified here. `Min`/`Max`/`Between` on a date property are a type branch in the size and bounds canvas.

Related canvases: attribute processing framework (`StaticAttributeProcessor`, registry); pipeline canvases (`ExampleGenerationStage` generates date, date-time and time examples from `format`).

Reverse-codified from the v0.4.0 code with `/spdd-reverse`.

## Requirements

- Publish a date field's shape as an OpenAPI `format` plus a sentence naming the PHP format the validator enforces.

## Entities

`DateFormatProcessor` implements `AttributeProcessor` directly. `Date` is a `StaticAttributeProcessor` row. No family-private base class.

## Approach

The OpenAPI `format` is mapped from a closed list of PHP formats; the sentence always states the PHP format string as declared. The mapping is exact only for `Y-m-d` (`date`), `Y-m-d\TH:i:s\Z`, `Y-m-d\TH:i:sP` and `c` (`date-time`) and `H:i:s` (`time`, as RFC 3339 partial-time). For every other format the published schema contradicts the sentence (see Known divergences).

Known divergences:

- `mapDateFormat` returns `date` for any unlisted format, including time-like unknowns. `Y/m/d`, `m-d-Y`, `m/d/Y` and unlisted formats are published as `date` (RFC 3339 `full-date`, `YYYY-MM-DD`), and `H:i` as `time`, although valid values of those formats violate the published format. The explicit `date` branch returns what the fallback returns.
- The example follows the mapped OpenAPI format, not the declared PHP format (`date('Y-m-d')`, `iso8601()` ending `+0000`, or `time('H:i:s')`), so it fails the declared format for `H:i`, `Y/m/d`, `m-d-Y`, `m/d/Y`, `Y-m-d\TH:i:s\Z` and every unlisted format.
- Only the first declared format is documented: `DateFormat` is variadic, and `parameters()[0]` is the whole list. `DateFormat('Y-m-d', 'd/m/Y')` documents `Y-m-d` alone.
- The first entry is used without a type check. Spatie types the formats `…|ExternalReference`: an `ExternalReference` in first position throws a `TypeError` in `mapDateFormat`, failing the whole docs build; in a later position it is dropped silently. A `DateFormat` with no formats reads an undefined key 0. Untested.
- `format` is overwritten by the last attribute that writes it, including a format set earlier by `CustomTypeStage`.

## Structure

`Processors/DateFormatProcessor.php`; the `Date` row lives in `AttributeProcessorRegistry::registerDefaults()`.

## Operations

| Attribute | Processor | Reads / guards | Writes | Sentence | Requirement effect | Example rule |
| --- | --- | --- | --- | --- | --- | --- |
| `DateFormat` | `DateFormatProcessor` | `parameters()[0]`, when not null; its first entry when it is an array (Spatie always returns the list), else the value itself | `format` = `mapDateFormat(format)` | `Must be a valid date in the format <code>{php format}</code>.` | none | generated from the mapped `format` (string type, no explicit example, no enum) |
| `Date` | `StaticAttributeProcessor` | none | `format` = `date` | `Must be a valid date.` | none | `Y-m-d` |

`mapDateFormat`: `Y-m-d\TH:i:s\Z`, `Y-m-d\TH:i:sP`, `c` → `date-time`; `H:i:s`, `H:i` → `time`; `Y-m-d`, `Y/m/d`, `m-d-Y`, `m/d/Y` → `date`; anything else → `date`.

## Norms

- The sentence uses the original PHP format string, not the OpenAPI format.

## Safeguards

- `DateFormatProcessorTest` pins the mapping of `Y-m-d` (`date`), `Y-m-d\TH:i:s\Z` (`date-time`) and `H:i:s` (`time`). It has no case for an `ExternalReference` argument, several formats or an unlisted format.
