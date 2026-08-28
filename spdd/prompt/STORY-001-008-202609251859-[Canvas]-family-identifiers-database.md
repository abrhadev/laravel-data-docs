# Identifier and Database-Backed Attributes

Family canvas for STORY-001-008 (`requirements/[User-story-8]database-backed-validation-attributes.md`). Owns the registry rows `Email`, `Url`, `ActiveUrl`, `Uuid`, `Ulid`, `Password`, `IP`, `IPv4`, `IPv6` and `Json`. It owns no processor class: every row is a static row.

**Story not shipped yet.** This canvas is reverse-codified from the current code only. STORY-001-008 extends it with `/spdd-prompt-update` (`Exists`, `Unique`, `CurrentPassword`, `MacAddress`); nothing that story adds is specified here.

Related canvases: attribute processing framework (`StaticAttributeProcessor`, registry); pipeline canvases (`ExampleGenerationStage` picks a Faker helper by `format`, or regexifies `pattern`).

Reverse-codified from the v0.4.0 code with `/spdd-reverse`.

## Requirements

- Publish a field that must hold a well-known identifier or encoded value (email, URL, UUID, ULID, IP address, JSON, password) as an OpenAPI `format` or `pattern` plus a sentence.

## Entities

No classes of its own. Each row is `new StaticAttributeProcessor(format:, pattern:, description:)` in `AttributeProcessorRegistry::registerDefaults()`.

## Approach

A fixed format, pattern and sentence per attribute, with no dedicated class. `StaticAttributeProcessor` never reads the attribute, so arguments some of these attributes take (`Url` protocols, `Email` modes, `Password` rules) are not documented. `IP` has no OpenAPI format for "either version", so it is published as a sentence only.

Known divergences:

- `Url` and `ActiveUrl` publish format `uri`, but `ExampleGenerationStage` maps only `url` to a Faker helper, so their generated example is a random word, not a URL. `Json` likewise gets a basic string.
- `Password` is published as format `password` with a generic sentence; the rules a `Password` attribute configures are not documented, and the example (`password(12, 20)`) ignores them, including a minimum above 20.
- `#[Url('https')]` is documented as any URL, and `#[Email('rfc', 'dns')]` as any email address: the protocols and modes are not read.
- `format` wins over `pattern` in example generation, so `Ulid` combined with a format attribute gets that format's example.

## Structure

The ten rows sit in the static block at the top of `registerDefaults()`, interleaved with the static rows of other families (see the framework canvas).

## Operations

Every example rule below applies only to a property typed exactly `string` with no explicit example: `string[]` items get a word, and an explicit example skips generation.

| Attribute | Processor | Writes | Sentence | Requirement effect | Example rule |
| --- | --- | --- | --- | --- | --- |
| `Email` | `StaticAttributeProcessor` | `format` = `email` | `Must be a valid email address.` | none | Faker `safeEmail()` |
| `Url` | `StaticAttributeProcessor` | `format` = `uri` | `Must be a valid URL.` | none | basic string (`uri` is not a Faker-mapped format) |
| `ActiveUrl` | `StaticAttributeProcessor` | `format` = `uri` | `Must be an active URL.` | none | as `Url` |
| `Uuid` | `StaticAttributeProcessor` | `format` = `uuid` | `Must be a valid UUID.` | none | Faker UUID |
| `Ulid` | `StaticAttributeProcessor` | `pattern` = `^[0-9A-HJKMNP-TV-Z]{26}$` | `Must be a valid ULID.` | none | `regexify(pattern)` |
| `Password` | `StaticAttributeProcessor` | `format` = `password` | `Must be a valid password.` | none | Faker password, 12–20 characters |
| `IP` | `StaticAttributeProcessor` | nothing | `Must be a valid IP address.` | none | basic string |
| `IPv4` | `StaticAttributeProcessor` | `format` = `ipv4` | `Must be a valid IPv4 address.` | none | Faker IPv4 |
| `IPv6` | `StaticAttributeProcessor` | `format` = `ipv6` | `Must be a valid IPv6 address.` | none | Faker IPv6 |
| `Json` | `StaticAttributeProcessor` | `format` = `json` | `Must be a valid JSON string.` | none | basic string (`json` is not a Faker-mapped format) |

## Norms

- A new attribute that needs only a fixed format, pattern and sentence is a row here, not a class.

## Safeguards

- No row reads its attribute instance, so no row can throw or dereference anything.
