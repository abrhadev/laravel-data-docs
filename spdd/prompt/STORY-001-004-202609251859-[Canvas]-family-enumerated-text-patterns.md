# Enumerated Values and Text Pattern Attributes

Family canvas for STORY-001-004 (`requirements/[User-story-4]enumerated-value-validation-attributes.md`). Owns `RegexProcessor`, `StartsWithProcessor`, `EndsWithProcessor`, and the registry rows `Regex`, `StartsWith`, `EndsWith`, `Alpha`, `AlphaDash`, `AlphaNumeric`, `Lowercase`, `Uppercase`.

**Story not shipped yet.** This canvas is reverse-codified from the current code only. STORY-001-004 extends it with `/spdd-prompt-update` (`Enum`, `NotRegex`, `DoesntStartWith`, `DoesntEndWith`); nothing that story adds is specified here.

Related canvases: attribute processing framework (`StaticAttributeProcessor`, registry); pipeline canvases (`ExampleGenerationStage` regexifies `pattern` for string examples).

Reverse-codified from the v0.4.0 code with `/spdd-reverse`.

## Requirements

- Publish a string field's required shape as an OpenAPI `pattern` plus a sentence: a declared regex, a required prefix or suffix, or a fixed character class.

## Entities

`RegexProcessor`, `StartsWithProcessor` and `EndsWithProcessor` implement `AttributeProcessor` directly. The five character-class rows are `StaticAttributeProcessor` instances. No family-private base class.

## Approach

**An argument is used as declared.** Each processor reads its argument from `parameters()` and writes it without checking its type. `Regex` skips only a null argument, and `StartsWith`/`EndsWith` only an empty `parameters()`, which Spatie never returns.

Known divergences:

- Spatie types the arguments of `Regex`, `StartsWith` and `EndsWith` as `…|ExternalReference`. An `ExternalReference` throws a `TypeError` (`Regex` assigns it to the `?string` field `pattern`; `StartsWith`/`EndsWith` pass it to `preg_quote`), failing the whole docs build. Untested.
- Spatie accepts a `StartsWith`/`EndsWith` with no values, and its `parameters()` is then `[[]]`: a bare `#[StartsWith]` publishes the match-everything pattern `^()` (`()$` for `EndsWith`) and the sentence `Must start with one of: .`
- `StartsWith`/`EndsWith` overwrite `pattern` entirely; they do not AND with an existing pattern. So does `Regex`, and so do the static rows.
- `Regex` publishes the declared PHP regex as written, delimiters and modifiers included (`/^[a-z]+$/`), not converted to an ECMA-262 pattern, so an OpenAPI consumer reads the slashes as literal characters. Faker's `regexify` strips a leading `/^` and a trailing `$/`, so the example survives a plain pattern, but not a modifier such as `/…/i`.
- The character-class patterns are ASCII-only and stricter than Laravel: `alpha`, `alpha_dash` and `alpha_num` accept Unicode letters and marks, and `lowercase` / `uppercase` accept any string without an upper-case (respectively lower-case) character.
- Values interpolated into sentences are not escaped; `preg_quote` is applied only to the `pattern`.

## Structure

`Processors/RegexProcessor.php`, `StartsWithProcessor.php`, `EndsWithProcessor.php`; the static rows live in `AttributeProcessorRegistry::registerDefaults()`.

## Operations

The example rule `regexify(pattern)` applies only to a property typed exactly `string`, with no explicit example, no enum and no `format`: `format` wins over `pattern`, and `string[]` items get a word. So `#[Email, StartsWith('a')]` gets an email example that fails the published pattern.

| Attribute | Processor | Reads / guards | Writes | Sentence | Requirement effect | Example rule |
| --- | --- | --- | --- | --- | --- | --- |
| `Regex` | `RegexProcessor` | first parameter, when not null | `pattern` = the declared PHP regex, as written | `Must match the regex <code>{pattern}</code>.` | none | `regexify(pattern)` |
| `StartsWith` | `StartsWithProcessor` | the list: `parameters()[0]` when it is an array, else the whole `parameters()` (Spatie always returns `[$values]`); skip only when `parameters()` is empty | `pattern` = `^(a|b|c)`, each value `preg_quote`d with delimiter `/` | `Must start with one of: <code>a</code>, <code>b</code>.` | none | `regexify(pattern)` |
| `EndsWith` | `EndsWithProcessor` | same as `StartsWith` | `pattern` = `(a|b|c)$` | `Must end with one of: <code>a</code>, <code>b</code>.` | none | `regexify(pattern)` |
| `Alpha` | `StaticAttributeProcessor` | none | `pattern` = `^[a-zA-Z]+$` | `Must contain only letters.` | none | `regexify(pattern)` |
| `AlphaDash` | `StaticAttributeProcessor` | none | `pattern` = `^[a-zA-Z0-9_-]+$` | `Must contain only letters, numbers, dashes, and underscores.` | none | `regexify(pattern)` |
| `AlphaNumeric` | `StaticAttributeProcessor` | none | `pattern` = `^[a-zA-Z0-9]+$` | `Must contain only letters and numbers.` | none | `regexify(pattern)` |
| `Lowercase` | `StaticAttributeProcessor` | none | `pattern` = `^[a-z]+$` | `Must contain only lowercase letters.` | none | `regexify(pattern)` |
| `Uppercase` | `StaticAttributeProcessor` | none | `pattern` = `^[A-Z]+$` | `Must contain only uppercase letters.` | none | `regexify(pattern)` |

## Norms

- Values in sentences are wrapped in `<code>`; no field names appear in this family today.

## Safeguards

- `RegexProcessorTest` pins the pattern and sentence for one regex; `StartsWithProcessorTest` and `EndsWithProcessorTest` pin a single and several values. No file has a case for an `ExternalReference` argument or a bare declaration.
