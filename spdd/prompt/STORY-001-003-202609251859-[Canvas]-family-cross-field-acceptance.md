# Cross-Field Comparison and Acceptance Attributes

Family canvas for STORY-001-003 (`requirements/[User-story-3]cross-field-comparison-validation-attributes.md`). Owns `SameProcessor`, `DifferentProcessor`, `InArrayProcessor`, `ConfirmedProcessor`, `AcceptedProcessor`, `AcceptedIfProcessor`, `DeclinedProcessor`, `DeclinedIfProcessor`, `Processors/Base/AcceptanceProcessor`, and the registry rows `Same`, `Different`, `InArray`, `Confirmed`, `Accepted`, `AcceptedIf`, `Declined`, `DeclinedIf`.

Related canvases: attribute processing framework (`ConditionProcessor`, `parametersOf`, `renderValues`, `fieldName`); documentation records (`ConfirmationCompanion`); DTO parameter extraction (`ParameterGenerator` publishes the companion `ConfirmedProcessor` records); pipeline canvases (`RequirementResolver` publishes `Accepted`/`Declined` fields as required and not nullable; `TypeStage` writes the `type` the acceptance example reads; `ExampleGenerationStage` skips a context whose example is set).

Provenance: created by `/spdd-reasons-canvas` from the analysis `spdd/analysis/GGQPA-XXX-202609251435-[Analysis]-cross-field-comparison-acceptance-attributes.md`. The story owner signed off on 2026-09-25 on the enforced companion name (D2), the literal bare `InArray` form (D4), acceptance and companion examples (D6), the value-list rendering, and the restated value set in conditional sentences (D5).

## Requirements

- State how a field's value must relate to another submitted field: equal to it (`#[Same]`), different from it (`#[Different]`), or one of its values (`#[InArray]`, documented as it really behaves).
- State on a `#[Confirmed]` field that a matching confirmation field must be sent, and record that companion, under the name runtime enforces, for the parameter generator to publish.
- State exactly which values count as acceptance or refusal (`#[Accepted]`, `#[Declined]`, `#[AcceptedIf]`, `#[DeclinedIf]`), unconditionally or under a stated condition.
- Give an `#[Accepted]` / `#[Declined]` field an example that passes its rule when no example was supplied.

## Entities

```mermaid
classDiagram
    class ConditionProcessor {
        <<abstract>>
    }
    class AcceptanceProcessor {
        <<abstract>>
        +ACCEPTED_VALUES array$
        +DECLINED_VALUES array$
        #valueList(array values) string
        #applyExample(ParameterContext context, bool accepted) void
    }
    AcceptanceProcessor --|> ConditionProcessor
    SameProcessor --|> ConditionProcessor
    DifferentProcessor --|> ConditionProcessor
    InArrayProcessor --|> ConditionProcessor
    ConfirmedProcessor --|> ConditionProcessor
    ConfirmedProcessor ..> ConfirmationCompanion : records on context
    AcceptedProcessor --|> AcceptanceProcessor
    DeclinedProcessor --|> AcceptanceProcessor
    AcceptedIfProcessor --|> AcceptanceProcessor
    DeclinedIfProcessor --|> AcceptanceProcessor
```

`AcceptanceProcessor` holds what the four acceptance processors share: the two value sets Laravel compares against, their rendering, and the example rule. It extends `ConditionProcessor` rather than `FieldReferenceProcessor` because the conditional pair needs `parametersOf` and `renderValues`. `Same`, `Different`, `InArray` and `Confirmed` extend `ConditionProcessor` directly, for `parametersOf`. `ComparisonProcessor` (size and bounds) is not reused for `Same`/`Different`: it exists to write numeric bounds, and field equality has no schema form.

## Approach

**A comparison names the other field as written, and never looks it up.** `Same`, `Different` and `InArray` always hold a single `FieldReference` (string input is wrapped). The processors render it through `fieldName(extractFieldName(...))`, so a reference to an undeclared field still renders and cannot fail the build.

**`InArray` is documented as it behaves.** `in_array:tags` compares against the key `tags` itself; only `in_array:tags.*` checks membership of an array field. The sentence is therefore chosen by the referenced name: one containing `*` states membership, with a single trailing `.*` stripped for display (`tags.*.id` is shown as written); one without `*` states equality, literally. A bare reference to an array field is documented as "Must equal the value of…" with no warning (D4, signed off).

**The confirmation field is named as runtime enforces it.** Laravel Data's `Confirmed` declares no constructor, so `parameters()` returns `[]`. What happens to `#[Confirmed('email_repeat')]` depends on the PHP version. PHP 8.4 discards the argument, and runtime still demands `<property>_confirmation`. PHP 8.3 refuses to instantiate the attribute (`Error: Attribute class … does not have a constructor, cannot pass arguments`), so Laravel Data cannot build the Data class at all, and documentation generation for it fails with the same error the application hits. Nothing is swallowed, because there is no enforced contract to document. `#[Rule('confirmed:email_repeat')]` is rebuilt through the same constructor-less class and loses the name too (analysis Result 1). `ConfirmedProcessor` therefore names the companion `$context->property->name . '_confirmation'`, and reads a name from `parameters()[0]` only when one is reported, so that an upstream change is followed rather than contradicted. The processor states the rule on the source and records a `ConfirmationCompanion` on the context: the name plus the companion's two sentences, so the `<b><i>` markup stays in this layer. It does not build the companion parameter. That needs the source's resolved `required`, `nullable` and final `example`, which exist only after `RequiredStage` and `ExampleGenerationStage`, and it is emitted by `ParameterGenerator` (DTO extraction canvas).

**Acceptance values are derived from the values Laravel compares against, never typed twice.** `ACCEPTED_VALUES` (`'yes', 'on', 1, '1', true, 'true'`) and `DECLINED_VALUES` (`'no', 'off', 0, '0', false, 'false'`) hold the PHP values of Laravel's `validateAccepted` / `validateDeclined`. The rendered text comes from them. The conditional forms restate the full value set after their condition (D5). Acceptance sentences state values only: `RequirementResolver` already publishes `Accepted`/`Declined` fields as required and not nullable, and the sentences neither repeat nor contradict that.

**An acceptance example passes the acceptance rule.** `AcceptedProcessor` and `DeclinedProcessor` set `example` from the value set by the documented `type`, only when no example is set yet. Package attributes are processed before validation attributes, so an explicit `#[Example]` wins, and `ExampleGenerationStage` skips a context whose example is set. `AcceptedIf`/`DeclinedIf` leave the example to generation, because their condition is not known per property. An enum property is also left to generation: `TypeStage` types an enum by its backing type (`string` or `integer`; a pure enum as `string`), and the value-set token for that type (`'yes'`, `1`) need not be one of its cases, whereas `ExampleGenerationStage` picks a case the schema's `enum` list admits.

Known divergences:

- Comparison sentences name the referenced field verbatim, and the confirmation companion's name uses the PHP property name. Input mapping (`MapInputName`/`MapName`) is out of scope, signed off.
- `#[Confirmed('email_repeat')]` is documented as `email_confirmation` on PHP 8.4+, and the developer is not warned that the argument is discarded (D2, signed off). On PHP 8.3 the declaration throws when Laravel Data reads the class, so generation fails as the application would. The throw comes from upstream and is not caught here.
- A bare `#[InArray('tags')]` on an array field is documented literally ("Must equal the value of…"), with no warning that it can never pass (D4, signed off).
- `#[Same('a'), Different('a')]` and `#[Accepted, Declined]` publish both sentences with no advisory, although no request can pass.
- `#[Same]` and `#[InArray]` examples are not aligned with the referenced field's example (D6(b), follow-up). An `#[AcceptedIf]`/`#[DeclinedIf]` field's example stays generated, so it may not satisfy the rule when the condition holds.
- An acceptance field typed `bool` is published as `type: boolean`, although validation also accepts `"yes"`, `"on"`, `"true"` and their refusal counterparts (D7). The type comes from `TypeStage`; this canvas does not widen it.
- Comparison, confirmation and acceptance rules declared as strings (`#[Rule('same:email')]`, `#[Rule('confirmed')]`, `#[Rule('accepted')]`) produce no sentence and no companion; only the attribute classes have processors. `RequirementResolver` (requirement resolution canvas) still counts `accepted`/`declined` rules towards requirement.
- A subclass of `Same`, `Different`, `InArray`, `Confirmed`, `Accepted`, `Declined`, `AcceptedIf` or `DeclinedIf` is not documented (exact-class registry lookup).

## Structure

`SameProcessor`, `DifferentProcessor`, `InArrayProcessor` and `ConfirmedProcessor` extend `ConditionProcessor`; `AcceptedProcessor`, `DeclinedProcessor`, `AcceptedIfProcessor` and `DeclinedIfProcessor` extend `Processors/Base/AcceptanceProcessor`, which extends `ConditionProcessor` and imports nothing beyond `ParameterContext`. None of the eight imports an upstream class. `ConfirmedProcessor` alone imports `ValueObjects\ConfirmationCompanion`, owned by the documentation records canvas.

## Operations

### AcceptanceProcessor

- Public constants `ACCEPTED_VALUES = ['yes', 'on', 1, '1', true, 'true']` and `DECLINED_VALUES = ['no', 'off', 0, '0', false, 'false']`, each with a one-line docblock saying it mirrors Laravel's `validateAccepted` / `validateDeclined` and is pinned by a validator test. They are public so that test can read them.
- `valueList(array $values): string`: maps each value to a token with `match (true)`: a bool → `true`/`false`; an int → its digits; a numeric string or `'true'`/`'false'` → the value in double quotes; anything else → the value bare. Each token is wrapped via `code()`. Returns `''` for an empty list and the single token for one value; otherwise all but the last joined with `, `, then `, or ` and the last. Carries a `@param` docblock only. The accepted set renders as `<code>yes</code>, <code>on</code>, <code>1</code>, <code>"1"</code>, <code>true</code>, or <code>"true"</code>`.
- `applyExample(ParameterContext $context, bool $accepted): void`: returns immediately when `$context->example !== null` (like `ExampleGenerationStage`, it does not distinguish an explicit example of `null`), and when `$context->enumInfo !== null`, so an enum property keeps a generated case. Otherwise sets it by `$context->type`:

| `type` | accepted | declined |
| --- | --- | --- |
| `boolean` | `true` | `false` |
| `integer`, `number` | `1` | `0` |
| `string` | `'yes'` | `'no'` |
| anything else | not set | not set |

### Attributes

`{field}`, `{companion}` and `{source}` via `fieldName(...)`; `{value}` via `renderValues([$value])`. Every processor except `AcceptedProcessor`/`DeclinedProcessor` reads through `parametersOf` and returns when it is null.

| Attribute | Processor (base) | Reads / guards | Writes | Sentence | Requirement effect | Example rule |
| --- | --- | --- | --- | --- | --- | --- |
| `Same` | `SameProcessor` (`ConditionProcessor`) | `extractFieldName(parameters[0])`; skip when index 0 is unset or the name is `''` | `descriptions[]` | `Must match the value of {field}.` | none | generated |
| `Different` | `DifferentProcessor` (same) | same as `Same` | `descriptions[]` | `Must differ from the value of {field}.` | none | generated |
| `InArray` | `InArrayProcessor` (same) | same as `Same` | `descriptions[]` | name contains `*`: `Must be one of the values submitted in {field}.` with one trailing `.*` stripped; otherwise `Must equal the value of {field}.` | none | generated |
| `Confirmed` | `ConfirmedProcessor` (same) | companion name: `extractFieldName(parameters[0])` when set and non-empty, else `{property}_confirmation` | `descriptions[]`, `confirmationCompanion` | source: `A matching {companion} value must be sent with it.`; companion match: `Must match the value of {source}.`; companion required-when-sent: `Required when {source} is sent.` | none on the source; the companion mirrors it (DTO extraction) | generated; the companion copies the source's |
| `Accepted` | `AcceptedProcessor` (`AcceptanceProcessor`) | no parameters | `descriptions[]`, `example` when null | `Must be sent as one of {valueList(ACCEPTED_VALUES)}.` | key-demanding and null-rejecting (requirement resolution canvas): required unless `Sometimes` or a default applies; never nullable | `applyExample(true)` |
| `Declined` | `DeclinedProcessor` (same) | no parameters | `descriptions[]`, `example` when null | `Must be sent as one of {valueList(DECLINED_VALUES)}.` | as `Accepted` | `applyExample(false)` |
| `AcceptedIf` | `AcceptedIfProcessor` (same) | index 0 field, single value at index 1 (`create()` turns `'true'`/`'false'` into bools); skip when shorter than two | `descriptions[]` | `Must be accepted when {field} is {value}, by sending one of {accepted list}.` — degraded, when `renderValues` is null: `Must be accepted when {field} has certain values, by sending one of {accepted list}.` | none | generated |
| `DeclinedIf` | `DeclinedIfProcessor` (same) | same as `AcceptedIf` | `descriptions[]` | `Must be declined when {field} is {value}, by sending one of {declined list}.` — degraded: `Must be declined when {field} has certain values, by sending one of {declined list}.` | none | generated |

- Sentence constants: `SameProcessor`, `DifferentProcessor`, `AcceptedProcessor` and `DeclinedProcessor` each hold a private `SENTENCE`; `AcceptedIfProcessor` and `DeclinedIfProcessor` hold `SENTENCE` and `DEGRADED_SENTENCE`; `InArrayProcessor` holds `MEMBER_SENTENCE` (wildcard) and `EQUAL_SENTENCE`.
- `InArrayProcessor`: a one-line comment above the branch records that `in_array` matches keys by pattern, so only a wildcard reference tests membership of an array field; it is the only comment in the class.

| Referenced name | Sentence |
| --- | --- |
| `tags.*` | `Must be one of the values submitted in <b><i>tags</i></b>.` |
| `tags.*.id` | `Must be one of the values submitted in <b><i>tags.*.id</i></b>.` |
| `tags` | `Must equal the value of <b><i>tags</i></b>.` |

- Rendered acceptance sentences: `Accepted` → `Must be sent as one of <code>yes</code>, <code>on</code>, <code>1</code>, <code>"1"</code>, <code>true</code>, or <code>"true"</code>.`; `Declined` → `Must be sent as one of <code>no</code>, <code>off</code>, <code>0</code>, <code>"0"</code>, <code>false</code>, or <code>"false"</code>.`
- `ConfirmedProcessor` sets `$context->confirmationCompanion = new ConfirmationCompanion($name, $matchSentence, $requiredWhenSentSentence)` with the constants `SOURCE_SENTENCE`, `MATCH_SENTENCE` and `REQUIRED_WHEN_SENT_SENTENCE`; both companion sentences name the source via `fieldName($context->property->name)`. It records the companion whatever the property's type or visibility; whether it is published, and where, is decided by `ParameterGenerator`. Class docblock, two sentences: Laravel Data's `Confirmed` accepts no argument (PHP 8.4 discards one; PHP 8.3 rejects the declaration), so the default name is what runtime enforces; a name is read from `parameters()` only so that an upstream change is followed rather than contradicted.
- Only `AcceptedProcessor` and `DeclinedProcessor` call `applyExample`; they do not read parameters.

## Norms

- Sentence text lives in private class constants. The two value sets on `AcceptanceProcessor` are the only public constants; the rendered value lists are derived from them, never typed separately.
- Comparison sentences open with "Must match", "Must differ", "Must be one of the values submitted in" or "Must equal"; acceptance sentences with "Must be sent as one of", "Must be accepted when" or "Must be declined when".

## Safeguards

- Write scope: all eight write `descriptions[]`. `AcceptedProcessor` and `DeclinedProcessor` may also write `example`, and only when it is null. `ConfirmedProcessor` may also write `confirmationCompanion`. None writes a requirement field, `type`, `format`, `pattern` or a numeric constraint. Each processor's Pest file asserts that `required`, `nullable`, `format`, `pattern` and `minLength` stay at their defaults.
- No processor looks up the field it names. `CrossFieldComparisonTest` pins a `#[Same]` reference to an undeclared field (AC10).
- No companion is named after an argument that `Confirmed::parameters()` does not report. `CrossFieldComparisonTest` pins that `new Confirmed()` still reports `[]`, so an upstream change surfaces as a failing test, not as silent drift. On PHP 8.4+ it pins that `#[Confirmed('email_repeat')]` publishes `email_confirmation` (AC5). On PHP 8.3 it pins that the same declaration is rejected with PHP's constructor-less attribute error. The declaration sits in its own fixture class, so the error cannot take other cases down; `ConfirmedProcessorTest` pins that a reported name is followed.
- The published acceptance and refusal lists are derived from `ACCEPTED_VALUES` and `DECLINED_VALUES`. `CrossFieldComparisonTest` checks every constant value against Laravel's validator and asserts that the near-misses `'TRUE'`, `'Yes'`, `'ON'`, `2` fail `accepted` and `'FALSE'`, `'No'`, `'OFF'` fail `declined`.
- An example written by `#[Example]` is never overwritten by an acceptance processor. Pinned by `explicit_example` in `CrossFieldComparisonTest`.
- An acceptance processor never sets the example of an enum property, so it stays one of the published `enumValues`. Pinned by `enum_consent` in `CrossFieldComparisonTest` and by the enum case in `AcceptedProcessorTest` / `DeclinedProcessorTest`.
