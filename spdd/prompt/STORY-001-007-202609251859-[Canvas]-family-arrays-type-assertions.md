# Array and Type-Assertion Attributes

Family canvas for STORY-001-007 (`requirements/[User-story-7]array-and-type-assertion-validation-attributes.md`). Owns `DigitsProcessor`, `DigitsBetweenProcessor`, and the registry rows `Digits` and `DigitsBetween`.

**Story not shipped yet.** This canvas is reverse-codified from the current code only. STORY-001-007 extends it with `/spdd-prompt-update` (`ArrayType`, `ListType`, `StringType`, `IntegerType`, `BooleanType`, `Numeric`, `Distinct`, `RequiredArrayKeys`, `MinDigits`, `MaxDigits`); nothing that story adds is specified here. `Min`/`Max`/`Size` on an array property are a type branch in the size and bounds canvas.

Related canvases: attribute processing framework (registry); pipeline canvases (`ParameterContext`'s `minimum`/`maximum`; `ExampleGenerationStage` reads them).

Reverse-codified from the v0.4.0 code with `/spdd-reverse`.

## Requirements

- State the number of digits a numeric field must have, and publish it as a numeric range.

## Entities

`DigitsProcessor` and `DigitsBetweenProcessor` implement `AttributeProcessor` directly. No family-private base class.

## Approach

A digit count is encoded as an integer range: 3 digits → minimum 100, maximum 999. The count is used as declared, with no check beyond null.

Known divergences:

- The range encoding is wrong for numbers with leading zeros, and `Digits(1)` has minimum 1, so it excludes 0.
- The range is published on any documented type, but only an `integer` or `number` example reads it. A `string` property (the common case for a PIN or account number) gets a basic string example, and OpenAPI ignores `minimum`/`maximum` on a string schema.
- `DigitsBetween` does not check that min ≤ max: `DigitsBetween(5, 2)` publishes minimum 10000 and maximum 99.
- From 19 digits the nines overflow the `int` cast, and from 20 digits the minimum does too, so the published range is wrong. Not verified by execution.
- Both processors overwrite `minimum`/`maximum`, so the last declared attribute that writes them wins.
- `Digits(1)` renders "1 digits".
- A count of 0 (`Digits(0)`, `DigitsBetween(0, …)`) makes `str_repeat` throw a `ValueError` on its negative length, and an `ExternalReference` argument throws a `TypeError`; either fails the whole docs build. Untested.

## Structure

`Processors/DigitsProcessor.php` and `Processors/DigitsBetweenProcessor.php`.

## Operations

| Attribute | Processor | Reads / guards | Writes | Sentence | Requirement effect | Example rule |
| --- | --- | --- | --- | --- | --- | --- |
| `Digits` | `DigitsProcessor` | first parameter, when not null | `minimum` = `1` followed by `digits - 1` zeros; `maximum` = `digits` nines | `Must have exactly <code>{digits}</code> digits.` | none | `integer`/`number`: generation reads the range; `string`: basic string, range ignored |
| `DigitsBetween` | `DigitsBetweenProcessor` | `parameters()[0]` and `[1]`, both not null | same encoding: `minimum` from the first count, `maximum` from the second | `Must have between <code>{min}</code> and <code>{max}</code> digits.` | none | as `Digits` |

## Norms

- Digit counts in sentences are wrapped in `<code>`.

## Safeguards

- `DigitsProcessorTest` pins `Digits(4)` (1000–9999) and `Digits(1)` (1–9); `DigitsBetweenProcessorTest` pins `DigitsBetween(2, 8)` (10–99999999). Neither has a case for a count of 0 or an `ExternalReference` argument.
