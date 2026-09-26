# Date and Time Attributes

Family canvas for STORY-001-005 (`requirements/[User-story-5]date-and-time-comparison-validation-attributes.md`). Owns `DateFormatProcessor`, `DateProcessor` and the registry rows `DateFormat` and `Date`.

**Story not shipped yet.** This canvas was reverse-codified from the code, then extended by a fix that documents every declared format with an example that passes it. STORY-001-005 extends it with `/spdd-prompt-update` (`After`, `AfterOrEqual`, `Before`, `BeforeOrEqual`, `DateEquals`, `TimeZone`); nothing that story adds is specified here. Its field-or-literal bounds build on the framework's `FieldReferenceProcessor`, not on `ComparisonProcessor` (`INDEX.md`). `Min`/`Max`/`Between` on a date property are a type branch in the size and bounds canvas.

Related canvases: attribute processing framework (registry; `#[Rule('date_format:…')]` is documented through `DateFormatProcessor`, and a `DateFormat` a later same-class `#[Rule]` replaces is skipped); pipeline canvases (`ParameterContext::dateFormat`, and `ExampleGenerationStage`, which formats the example with it, or otherwise generates date, date-time and time examples from `format`).

Carved out of the former attribute processors canvas; git history keeps it.

## Requirements

- Publish a date field's shape as a sentence naming every PHP format the validator accepts, plus an OpenAPI `format` only where every valid value satisfies it.
- Give a `DateFormat` field an example that passes the declared `date_format` rule. For a format some dates cannot produce (`d/m`, read in 1970, has no 29 February), the example is redrawn a bounded number of times (example generation canvas), so it passes with near certainty rather than always.

## Entities

`DateFormatProcessor` and `DateProcessor` implement `AttributeProcessor` directly. No family-private base class.

## Approach

Laravel's `date_format` rule, verified on 2026-09-25 against a Data class's own `getValidationRules` and Illuminate's validator:

- takes every declared format, variadic or as an array (`DateFormat('Y-m-d', 'd/m/Y')` becomes `date_format:Y-m-d,d/m/Y`, `DateFormat(['Y-m-d', 'H:i'])` becomes `date_format:Y-m-d,H:i`), and a value passes when **any** format reads it back unchanged: `DateTime::createFromFormat('!' . format, value, UTC)` succeeds and `->format(format) == value`. `31/01/2024` and `2024-01-31` pass the first, `01/31/2024` does not;
- so a format containing a character `createFromFormat` cannot parse accepts no value at all: `c`, `r`, `N`, `W`, `L` and `t` reject every value, including `date('c')` and `date('r')`. `DateFormat('Y-m-d', 'c')` accepts `Y-m-d` values only;
- reads the formats as Laravel parses a rule string: Spatie joins them with commas (`date_format:D, d M Y`) and Laravel splits that with `str_getcsv`, so a format containing a comma is two formats: `DateFormat('D, d M Y')` accepts `Thu` and ` 17 Mar 1994`, and rejects `Thu, 17 Mar 1994`;
- `Y-m-d\TH:i:sP` accepts `2024-01-01T10:00:00+00:00` and rejects `+0000` and `Z`; `Y-m-d\TH:i:s\Z` accepts `…Z` only; `H:i` rejects `10:30:00`; `m/d/Y` rejects `2024-01-31`; `d/m` rejects `29/02` (read in 1970).

**Which formats are documented.** The declared list is `parameters()[0]`. If it is empty, or any entry is not a string (an `ExternalReference`, which is never dereferenced), nothing is written: part of the accepted set is unknown, so no sentence, format or example rule could be complete, as for `StartsWith`/`EndsWith`. Otherwise the list is read as Laravel reads it (`str_getcsv` of the comma-joined list, `"` enclosure, `\` escape) and deduplicated in order. A format is *satisfiable* when at least one of three fixed reference instants (`2001-02-03 04:05:06`, `2024-11-27 16:45:30`, `1999-12-31 23:59:59`, UTC), formatted with it, reads back unchanged the way the rule reads it; a `ValueError` counts as unsatisfiable. The documented formats are the satisfiable ones, in declared order; an unsatisfiable format is left out of the sentence, because no value of it passes.

**An impossible `DateFormat` is published as a note.** When no declared format is satisfiable, no request that sends the field passes, and the sentence is `Note: must be a valid date in the format <code>{f}</code>, which no value can match, so any request that sends this field fails validation.` (several: `in one of the formats: <code>{a}</code>, <code>{b}</code>, which no value can match, …`), with `format` cleared to null and no `dateFormat`.

**The OpenAPI `format` is published only where it matches exactly.** `EXACT_FORMATS` maps a closed list: `Y-m-d` → `date` (RFC 3339 `full-date`); `Y-m-d\TH:i:sP` and `Y-m-d\TH:i:s\Z` → `date-time`; `H:i:s` → `time` (RFC 3339 `partial-time`). Every other format maps to null. `format` is written as the mapping shared by **all** documented formats, and as null when one of them maps to null or two map differently, so a format written earlier (by `Date` or a custom-type config) that valid values would violate is cleared. The sentence still states each PHP format. `c` is no longer mapped: no value passes it.

**The example follows the declared PHP format.** `dateFormat` (pipeline-owned `ParameterContext` field) is set to the first documented format, or, when the property also carries a `Date`, declared or written as a rule string (`ReplacedRules::documentedRules` holds a `Date`, so `#[Rule('date')]` counts too, in either declaration order), the first documented format whose value Laravel's `date` rule reads as a calendar date (`dateReads`: for some reference instant, `strtotime` reads the formatted value and `date_parse` finds a valid year, month and day); `ExampleGenerationStage` formats a Faker date with it, so the example passes the rule (example generation canvas). With a pattern on the same field (`StartsWith`, `EndsWith`, `Regex`), the stage keeps the first drawn date that matches it; failing that it lays the pattern's own example over the start or end of a drawn date and keeps the result when it still reads back under the format; only then the pattern's example. Dates are drawn from fifty years either side of today, so `StartsWith('205')` is met by the draws, and a narrow prefix such as `StartsWith('2031')` or `StartsWith('2031-05')` through the overlay; a pattern no overlay turns into a date that reads back under the format falls back to the pattern's example, which fails `date_format`. With a `Date` beside the `DateFormat`, the example must also pass `date`, which reads `d/m/Y` month first, so `#[Date, DateFormat('d/m/Y')]` gets a date whose day is at most 12 (`DatesTimesTest` `day_first_date`), and `#[Date, DateFormat('H:i', 'Y-m-d')]` draws from `Y-m-d`, the format `date` reads (`time_then_date`; `#[Rule('date'), DateFormat('H:i', 'Y-m-d')]` likewise, `rule_date_then_formats`). Beside a `Date` only the formats `dateReads` accepts are listed in the sentence and decide `format`, since no value in another passes both rules: `#[Date, DateFormat('H:i', 'Y-m-d')]` reads "Must be a valid date in the format <code>Y-m-d</code>." (`DatesTimesTest` "lists beside a Date, declared or written as a rule, only the formats the date rule reads"). Without a `Date`, any documented format would do, since one passing format is enough.

**A `Date` beside formats it never reads is published as a note.** When no documented format passes `dateReads` (`#[Date, DateFormat('H:i')]`, `#[DateFormat('d/m'), Date]`, `#[DateFormat('H:i'), Rule('date')]`: `date_parse` finds no year), no value passes both rules, so `DateFormatProcessor` writes `Note: must be a valid date in the format <code>H:i</code>, which the date rule never reads as a date, so any request that sends this field fails validation.` and no `format` or `dateFormat` (`DatesTimesTest` "publishes Date beside a format date never reads as a note, in either order"); `Date` still adds its own sentence.

**Both rules narrow an `#[In]` set.** `DateProcessor` appends `date`, and `DateFormatProcessor` appends `date_format:{formats}` (the split formats joined with `,`, as Laravel reads them), to `valueRules`, so an `#[In]` value either rule rejects is neither published nor drawn (`InTest` `in_date`, `in_date_format`). The array branches append nothing.

**A `DateFormat` on an array field is published as a note.** Laravel's `date_format` rejects any value that is not a string or a number, so `#[DateFormat('Y-m-d')] array $dates` fails every request that sends it: the sentence becomes `Note: must be a valid date in the format <code>Y-m-d</code>, which no array can be, so any request that sends this field fails validation.`, and no `format` or `dateFormat` is written. `DatesTimesTest` pins it against the validator. `Date` on an array field is published the same way, since `date` rejects any value that is not a string, a number or a date object: `DateProcessor` writes only `Note: must be a valid date, which no array can be, so any request that sends this field fails validation.` and no `exampleFormat` (`DatesTimesTest` `any_date_array`). With both a `Date` and a `DateFormat` on an array field, both notes are published for the same impossibility; they are redundant, not false.

**A `Date` with a pattern gets a date that meets it.** `Date`'s `exampleFormat` `date` is served from the same fifty-year range either side of today (example generation canvas), so `#[Date, StartsWith('205')]` gets a matching `Y-m-d` date (`DatesTimesTest` `any_date_future`), and a narrow prefix is met by laying it over a drawn date, as for `DateFormat` (`any_date_narrow`, `StartsWith('2031-05')`), even a year outside the draw range (`far_past_prefix`, `StartsWith('1850')`). With a prefix and a suffix, each is laid over the date in turn and the example is kept only when it passes every pattern rule and reads as a date (`two_patterns`, `#[DateFormat('Y-m-d'), StartsWith('20'), EndsWith('-01')]`). A `Regex` that publishes no pattern beside a date format is honoured the same way: its body is drawn from and laid over a drawn date, and the declared regex decides (`date_regex_insensitive`, `#[DateFormat('Y-m-d'), Regex('/^2031/i')]`).

Known divergences:

- Two `date_format` rules in one `#[Rule]` (`Rule('date_format:Y-m-d|date_format:d/m/Y')`) are both enforced, so no value passes, yet both are documented as ordinary sentences.

- `format` is overwritten by the last attribute that writes it; `Date` writes none, so `#[DateFormat('d/m/Y'), Date]` keeps the `DateFormat`'s (none for `d/m/Y`). `dateFormat` is written only by `DateFormat`, so the example follows it rather than `Date`'s `exampleFormat`, drawn until it passes `date` as well (above).
- `DateFormat` on a `string` field that also has a literal comparison bound gets the numeric-string example, which the pipeline generates before any date format.
- `H:i:s` is published as `time`. JSON Schema's `time` is RFC 3339 `full-time`, which also demands an offset; OpenAPI 3.0 defines no `time` format. The mapping follows the RFC 3339 `partial-time` reading.
- A format containing a comma is documented as the two formats Laravel enforces, not as declared.
- `DateFormatProcessor` calls `parameters()` directly.
- `#[Date] int` gets an integer example (1–100), which `date` rejects; the combination is contrived.

## Structure

`Processors/DateFormatProcessor.php`, `Processors/DateProcessor.php`; the `Date` row lives in `AttributeProcessorRegistry::registerDefaults()`.

## Operations

| Attribute | Processor | Reads / guards | Writes | Sentence | Requirement effect | Example rule |
| --- | --- | --- | --- | --- | --- | --- |
| `DateFormat` | `DateFormatProcessor` | `parameters()[0]`, the format list, read as Laravel splits it and deduplicated; nothing written when it is empty or any entry is not a string; documented formats = the satisfiable ones; a `…[]` type: the array note, nothing else written | `format` = the `EXACT_FORMATS` mapping all documented formats share, else null; `dateFormat` = the first documented format, or with a `Date` (declared or as a rule string) the first one `dateReads`, and only those formats listed and mapped to `format` (none: `format` = null, no `dateFormat`, the date-rule note); when none is satisfiable, `format` = null and no `dateFormat`; `valueRules` += `date_format:{formats}` (after the array guard) | one format: `Must be a valid date in the format <code>{f}</code>.`; several: `Must be a valid date in one of the formats: <code>{a}</code>, <code>{b}</code>.`; none satisfiable: the note above | none | a Faker date formatted with `dateFormat` (example generation canvas) |
| `Date` | `DateProcessor` | the context's `type`; a `…[]` type: the array note above, nothing else written | `exampleFormat` = `date` and `valueRules` += `date`; no published `format`, because Laravel's `date` rule accepts any `strtotime`-readable value (`2024-01-31 10:00:00`, `January 31, 2024`), which OpenAPI `date` would reject | `Must be a valid date.` | none | `Y-m-d`, which the rule accepts |

`DateFormatProcessor` private members: `REFERENCE_INSTANTS` and `EXACT_FORMATS` (the closed map above); the `str_getcsv` read of the declared formats, deduplicated, is inline in `process()`; `isSatisfiable(string $format): bool` (the three reference instants, `ValueError` caught, loose `==` as Laravel compares; its docblock records both why a comma splits a format and why `c` and `r` fail); `formatList(array $formats): string` (`the format <code>{f}</code>` or `one of the formats: <code>{a}</code>, <code>{b}</code>`).

## Norms

- The sentence uses the original PHP format strings, not the OpenAPI format, each wrapped in `<code>`, joined with `, `, in declared order.
- No declared format is resolved from the request, configuration or environment; the satisfiability check uses fixed instants, so repeated builds produce identical output.

## Safeguards

- `DateProcessorTest` pins the sentence and `exampleFormat` on a `string` field, and on an array field the note alone with no `exampleFormat`.
- `DateFormatProcessorTest` has a case for an `ExternalReference` argument, alone and after a literal format, and pins: several formats in the sentence; `format` only for an exact, shared mapping (null for `H:i`, `Y/m/d`, `m-d-Y`, `m/d/Y`, `c`, an unlisted format, and mixed `Y-m-d` / `H:i:s`); an unsatisfiable format left out; the note when none is satisfiable; `dateFormat` set to the first documented format.
- `tests/Integration/Pipeline/DatesTimesTest.php` is the family's acceptance check. Its fixtures also cover a `DateFormat` with a `StartsWith` pattern (`with_pattern`, `future_pattern`), narrow prefixes (`narrow_year` `StartsWith('2031')`, `narrow_month` `StartsWith('2031-05')`), `#[Date]` (`any_date`, `any_date_future` with a `StartsWith('205')` pattern, `any_date_narrow` with `StartsWith('2031-05')`, and the array `any_date_array`), `#[Date, DateFormat('d/m/Y')]` (`day_first_date`), two patterns (`two_patterns`), a prefix outside the draw range (`far_past_prefix`) and a `DateFormat` array (`date_array`). Through the default pipeline, it validates the generated example of a fixture Data class's `DateFormat` properties (each mapped format, `H:i`, `Y/m/d`, `m-d-Y`, `m/d/Y`, `d/m`, an unlisted format, a format with a comma, several formats variadic and as an array, a mix with `c`) against that class's own `getValidationRules` with Illuminate's validator over repeated runs; pins that `date_format` accepts a value of any declared format, that `c` and `r` accept no value, and that a comma splits a format, so a change in how Laravel reads the rule fails the suite.
