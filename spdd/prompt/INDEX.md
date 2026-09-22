# SPDD canvas map

This file is an ownership index, not a REASONS canvas. Do not generate code from it.

Every source file, and every row of the attribute processor registry's default table, is owned by exactly one canvas. Collaborators are named in other canvases; they are not re-specified there.

Canvases come in two kinds:

- **Core canvases** own a layer and specify its contracts: types, call shapes, ordering, invariants and extension points. A core canvas never specifies a processor or a registry row. It names validation attributes only where its own code does: `RequirementResolver`'s rule sets and `RequirementDescriptionStage`'s sentences decide requirement status for whole groups of attributes, and belong to the requirement resolution canvas. It changes only when a contract changes.
- **Family canvases** own one family of validation attributes: its processors, its family-private base class, and its registry rows. A family canvas is the lasting owner of its user story. It is never folded away or deleted.

## Core canvases

| Canvas | File | Owns |
| --- | --- | --- |
| Parameter metadata pipeline | `LDD-202608281600-[Canvas]-pipeline-parameter-metadata.md` | `src/Pipeline/**` except the two rows below: the stage contract and runner, `PipelineFactory` and the default stage order, `ParameterContext`, and the hidden, type, custom-type, attribute-processing, type-description and default-value stages |
| Requirement resolution | `LDD-202609260836-[Canvas]-pipeline-requirement-resolution.md` | `Pipeline/Stages/RequiredStage.php`, `Pipeline/Stages/RequirementDescriptionStage.php`, `Pipeline/Support/**` (the requirement seam: `RequirementResolver`, `RequirementStatus`), and the `Required`, `Nullable` and `Sometimes` handling |
| Example generation | `LDD-202609260836-[Canvas]-pipeline-example-generation.md` | `Pipeline/Stages/ExampleGenerationStage.php` |
| Documentation records | `LDD-202608281600-[Canvas]-vo-documentation-records.md` | `src/ValueObjects/**` |
| Public attributes | `LDD-202608281600-[Canvas]-api-public-attributes.md` | `src/Attributes/**`; `DescriptionProcessor`, `ExampleProcessor`, `QueryParameterProcessor`; registry rows `Description`, `Example`, `QueryParameter` |
| Attribute processing framework | `LDD-202609251859-[Canvas]-service-attribute-processing-framework.md` | `AttributeProcessor`, `AttributeProcessorRegistry` (lookup, override, and the order of registration, not the rows), `StaticAttributeProcessor` (the mechanism, not its rows), and the shared sentence rules (`<code>` tokens, complete sentences) |
| Custom type extension point | `LDD-202608281600-[Canvas]-service-custom-type-extension.md` | `src/CustomTypeProcessing/**` |
| DTO parameter extraction | `LDD-202608281600-[Canvas]-service-dto-parameter-extraction.md` | `src/Services/**` |
| Scribe strategies | `LDD-202608281600-[Canvas]-api-scribe-strategies.md` | `src/Strategies/**` |
| Package + OpenAPI glue | `LDD-202608281600-[Canvas]-api-package-openapi-glue.md` | `src/LaravelDataDocsServiceProvider.php`, `src/OpenApi/**`, `config/data-docs.php` |

## Family canvases

All processor paths are under `src/AttributeProcessing/Processors/`. "Rows" means the family's entries in `AttributeProcessorRegistry::registerDefaults()`.

Every existing processor and row belongs to its final family from the start. A family whose story has not shipped yet starts with the existing attributes it will own, reverse-codified from the code as it is. Its story then extends that canvas with `/spdd-prompt-update`. Nothing moves between families later.

| Family | Story | File | Processors and base class | Rows | Added by its story |
| --- | --- | --- | --- | --- | --- |
| Conditional requirement | STORY-001-001 (+ `Filled` from STORY-001-000) | `STORY-001-001-{ts}-[Canvas]-family-conditional-requirement.md`, created by that story; no existing class belongs to it | — | — | `RequiredIf`, `RequiredUnless`, `RequiredWith`, `RequiredWithAll`, `RequiredWithout`, `RequiredWithoutAll`, `Filled` |
| Prohibition and exclusion | STORY-001-002 | `STORY-001-002-{ts}-[Canvas]-family-prohibition-exclusion.md`, created by that story; no existing class belongs to it | — | — | `Prohibited`, `ProhibitedIf`, `ProhibitedUnless`, `Prohibits`, `Exclude`, `ExcludeIf`, `ExcludeUnless`, `ExcludeWith`, `ExcludeWithout` |
| Cross-field comparison and acceptance | STORY-001-003 | `STORY-001-003-{ts}-[Canvas]-family-cross-field-acceptance.md`, created by that story; no existing class belongs to it | — | — | `Same`, `Different`, `InArray`, `Confirmed`, `Accepted`, `AcceptedIf`, `Declined`, `DeclinedIf` |
| Size and bounds | pre-SPDD (extended by STORY-001-005/006/007) | `LDD-202609251859-[Canvas]-family-size-bounds.md` | `Min`, `Max`, `Between`, `Size`, `MultipleOf`, `GreaterThan`, `GreaterThanOrEqualTo`, `LessThan`, `LessThanOrEqualTo`; `Base/SizeBasedProcessor`, `Base/ComparisonProcessor` | those nine | type branches (see below) |
| Enumerated values and text patterns | STORY-001-004 | `STORY-001-004-202609251859-[Canvas]-family-enumerated-text-patterns.md` | `Regex`, `StartsWith`, `EndsWith` | those three, plus the static rows `Alpha`, `AlphaDash`, `AlphaNumeric`, `Lowercase`, `Uppercase` | `In`, `NotIn`, `Enum`, `NotRegex`, `DoesntStartWith`, `DoesntEndWith` |
| Dates and times | STORY-001-005 | `STORY-001-005-202609251859-[Canvas]-family-dates-times.md` | `DateFormat` | `DateFormat`, plus the static row `Date` | `After`, `AfterOrEqual`, `Before`, `BeforeOrEqual`, `DateEquals`, `TimeZone` |
| Arrays and type assertions | STORY-001-007 | `STORY-001-007-202609251859-[Canvas]-family-arrays-type-assertions.md` | `Digits`, `DigitsBetween` | those two | `ArrayType`, `ListType`, `StringType`, `IntegerType`, `BooleanType`, `Numeric`, `Distinct`, `RequiredArrayKeys`, `MinDigits`, `MaxDigits` |
| Identifiers and database-backed | STORY-001-008 | `STORY-001-008-202609251859-[Canvas]-family-identifiers-database.md` | none of its own (static rows only) | the static rows `Email`, `Url`, `ActiveUrl`, `Uuid`, `Ulid`, `Password`, `IP`, `IPv4`, `IPv6`, `Json` | `Exists`, `Unique`, `CurrentPassword`, `MacAddress` |
| Files and uploads | STORY-001-006 | `STORY-001-006-{ts}-[Canvas]-family-files-uploads.md`, created by that story; no existing class belongs to it | — | — | `File`, `Image`, `Mimes`, `MimeTypes`, `Dimensions` |

### Known cross-family dependency

`Min`, `Max`, `Between` and `Size` pick their wording by property type in `SizeBasedProcessor`. Stories 005 (dates), 006 (files) and 007 (arrays) each add a type branch there. That branch is specified in the **Size and bounds** canvas, and the story's own family canvas refers to it. This is the one planned case of a story editing a second family.

## Rules

**Ownership**

- Ownership is fixed from the start; no class or row changes canvas later. Shared base classes are in the framework canvas. A family's own base class (`SizeBasedProcessor`, `ComparisonProcessor`) is extended only inside that family. A new family that needs similar behaviour builds on a shared base in the framework canvas, never on another family's base.
- A family canvas specifies its own registry rows. The framework canvas specifies how registration works and in what order the families register.
- Adding a field to `ParameterContext`, a value object, or a `ParameterGenerator` step is a core contract change. Update that core canvas in the same plan commit as the family canvas that needs it.
- Not in any canvas: tests, `jig`, CI, Pint, PHPStan, Composer lockfiles, changelog. A family canvas may name its integration test (`tests/Integration/Pipeline/<Family>Test.php`) as its acceptance check.

**Naming**

SPDD files keep the shape the SPDD commands use, `{ID}-{TIMESTAMP}-[{TAG}]-{scope}-{description}.md`. This repo fixes what each part means:

| Part | Rule |
| --- | --- |
| `ID` | For a family canvas: its story ID from `requirements/`, e.g. `STORY-001-005`. For a canvas that comes from no story (the core canvases, and the pre-SPDD *Size and bounds* family): `LDD`, the package's own key. For a spike's analysis: its spike ID, e.g. `SPIKE-001`. `GGQPA-XXX` is the SPDD commands' fallback for a missing Jira ticket and is never used here. |
| `TIMESTAMP` | `YYYYMMDDHHmm`, set when the file is first created and never changed afterwards, including on renames. |
| `TAG` | `[Canvas]` for every lasting owner canvas, core or family, whichever command created it. `[Feat]`, `[Fix]` and `[Refactor]` only for frozen change canvases under `spdd/changes/`. `[Analysis]` and `[Review]` as the SPDD commands write them. `[Codify]` is not used as a file tag: it records how a canvas was first written, not what it is, and the canvas header records that provenance. |
| `scope` | Core canvases: their layer (`pipeline`, `vo`, `api`, `service`). Family canvases: always `family`. |
| `description` | Kebab-case, stable. Renaming a canvas means changing this file in the same commit. |

- An analysis or review file takes the `ID` of what it is about: `STORY-001-005-{ts}-[Analysis]-…` for a story's analysis; `STORY-001-005-{ts}-[Review]-family-dates-times.md` for a review of that family canvas. `/spdd-code-review` is told to take the ID from the canvas file name; check the name it proposes.
- User stories keep the `/spdd-story` convention, `requirements/[User-story-N]{title}.md`, and carry their story ID in the heading.
- The SPDD commands fall back to `GGQPA-XXX` and choose their own tag. Give them the target file name, or `git mv` the generated file to its proper name before committing.
- Files written before these rules, in `spdd/analysis/` and `spdd/review/`, keep their names; they are history. The rules apply to every new file and to every canvas in this index.

**Size**

- A family canvas's Operations section is one table with one row per attribute: attribute → processor / base class → reads and guards → context fields written → sentence template → requirement effect → example rule. A column that is the same for every row may be stated once above the table instead. Prose subsections are only for a family-private base class or behaviour that a row cannot express.
- Exact sentence wording is pinned by tests. A canvas gives the template, not every rendered sentence.
- A canvas that passes about 300 lines is a signal to split it along the axis its changes follow.

**Workflow for a story**

1. `/spdd-story` → `requirements/`, then `/spdd-analysis` → `spdd/analysis/`. Both are kept.
2. **Plan commit** (`docs(spdd): …`): extend the story's family canvas with `/spdd-prompt-update`, or create it with `/spdd-reasons-canvas` if no existing class belongs to it (STORY-001-001, STORY-001-002, STORY-001-003 and STORY-001-006). Then run `/spdd-prompt-update` on each core canvas whose contract changes. Review this as a diff against canvases that stay.
3. **Feature commit** (`feat: …`): `/spdd-generate` from the family canvas, scoped to the plan commit's diff on core canvases, plus tests. Use `/spdd-sync` for anything the implementation had to change. Nothing is folded or deleted.

**Cross-cutting changes**

A change that alters contracts in two or more core canvases and belongs to no family may be planned in a change canvas under `spdd/changes/`. It is frozen once shipped, headed "Shipped — do not generate from this", and never kept in sync. The core canvases are updated with `/spdd-prompt-update` as usual.

**Reviews**

`spdd/review/` is gitignored (`.gitignore`), so review reports are local working files and git keeps none of them. Keep only the latest review per canvas there. A finding that matters beyond the review is recorded in the canvas it concerns, as a known divergence or a safeguard; a canvas never cites a review file by path, because a reader of the repository cannot open it.

