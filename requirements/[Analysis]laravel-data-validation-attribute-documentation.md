# Story Decomposition: Documentation for Missing Laravel Data Validation Attributes

**Feature owner audience**: Product Owner, Scrum Master, QA
**Module number**: `001`
**Generated**: 2026-09-22

> **✅ [SPIKE-001] resolved 2026-09-22.** The decomposition was revised against verified findings —
> see `spdd/analysis/GGQPA-XXX-202609221754-[Analysis]-validation-rule-source-architecture.md`.
> The generator keeps reading validation attributes through `AttributeProcessorRegistry`; it does **not**
> consume Laravel Data's payload-driven rule resolver. One foundational story (STORY-001-000) was added
> and STORY-001-001 was reduced accordingly. These stories are ready to take forward.

---

## INVEST Analysis

### Abstract Task: "Document the remaining Laravel Data validation attributes"

**Analysis Dimensions**

- **Core Responsibility**: When an API endpoint accepts a Laravel Data object, every validation rule declared on that object's properties should be visible to the person reading the generated API documentation. Today roughly thirty validation attributes are reflected in the generated docs; the rest are silently ignored, so the published contract understates what the API actually enforces.
- **Primary Operations**: For each unsupported validation attribute — recognise it on a Data property, translate it into the documented parameter contract (required flag, allowed values, formats, bounds), and express any conditional or cross-field semantics as a human-readable sentence in the parameter description.
- **Key Constraints**:
  - The package documents behaviour; it must never change how requests are actually validated at runtime.
  - Generated documentation must remain valid OpenAPI, so semantics that have no OpenAPI keyword must degrade into descriptive prose rather than invented schema keywords.
  - An unrecognised or malformed attribute must never break a documentation build — partial docs are acceptable, a failed build is not.
  - Several attributes reference *other* properties (`RequiredIf`, `Prohibits`, `Same`), so the description must name the referenced field in terms the API consumer recognises.
  - Requirement status is already derived from each property's declared type and default value. Attributes in this area must be reconciled with that existing derivation under one explicit precedence rule, not derived independently of it.
- **Technical Complexity**: Medium. The extension points for adding attribute coverage already exist and are already exercised by the thirty supported attributes; the work is repetitive translation plus a handful of genuinely tricky cases (cross-field references, file uploads, enums).
- **Business Complexity**: Medium-High. The attribute set is large (~50 uncovered attributes) and splits into families with clearly different documentation semantics: conditional requirement, prohibition, exclusion, comparison, enumeration, dates, files, types, and database-backed constraints.

### INVEST Evaluation (of the feature as a single unit)

- ❌ **Independent**: As one unit it is independent, but it bundles nine unrelated semantic families that have no reason to ship together.
- ✅ **Negotiable**: The wording of generated sentences and the depth of schema mapping are open to discussion.
- ✅ **Valuable**: Every family independently raises the fidelity of the published API contract.
- ❌ **Estimable**: "Document ~50 attributes" spans too wide a range to estimate with confidence in one piece.
- ❌ **Small**: Estimated at roughly four to five weeks of work — far beyond a single story.
- ✅ **Testable**: Each attribute has an observable effect on generated documentation.

**Conclusion**: Needs splitting.

### Split Strategy

**Dimension chosen**: by *semantic family of the attribute*, i.e. a split by feature area rather than by technical layer.

This dimension was chosen because each family answers a different question for the API consumer ("must I send this?", "may I send this?", "what values are allowed?", "what file may I upload?"). Families therefore carry independent business value, can be released in any order, and each maps to a coherent, estimable block of work. Splitting instead by technical layer (attribute recognition / schema mapping / description rendering) was rejected outright — it would produce stories that deliver nothing on their own.

**Split rules applied**: each story covers one semantic family, holds at most three core functional points, and is sized between one and five days.

**Revised after [SPIKE-001]**: one story does not follow the family dimension. STORY-001-000 is a
foundation story, split out because requirement status is decided in a different part of the system
from attribute translation, and because a live defect there would otherwise be inherited by
STORY-001-001. It is the only inter-story dependency in the module.

**Resulting stories**

| ID | Story | Family | Estimate |
| --- | --- | --- | --- |
| STORY-001-000 | Requirement status reconciliation (foundation) | Reconciles `Required`/`Nullable`/`Sometimes` with the declared type; fixes the ordering defect | 2 days |
| STORY-001-001 | Conditional requirement attributes | `RequiredIf`, `RequiredUnless`, `RequiredWith(All)`, `RequiredWithout(All)`, `Present`, `Filled` | 2 days |
| STORY-001-002 | Prohibition and exclusion attributes | `Prohibited`, `ProhibitedIf`, `ProhibitedUnless`, `Prohibits`, `Exclude`, `ExcludeIf`, `ExcludeUnless`, `ExcludeWith`, `ExcludeWithout` | 4 days |
| STORY-001-003 | Cross-field comparison and acceptance attributes | `Same`, `Different`, `Confirmed`, `InArray`, `Accepted`, `AcceptedIf`, `Declined`, `DeclinedIf` | 3 days |
| STORY-001-004 | Enumerated value and negative pattern attributes | `In`, `NotIn`, `Enum`, `NotRegex`, `DoesntStartWith`, `DoesntEndWith` | 3 days |
| STORY-001-005 | Date and time comparison attributes | `After`, `AfterOrEqual`, `Before`, `BeforeOrEqual`, `DateEquals`, `TimeZone` | 3 days |
| STORY-001-006 | File and upload attributes | `File`, `Image`, `Mimes`, `MimeTypes`, `Dimensions`, `Size` | 4 days |
| STORY-001-007 | Array and type-assertion attributes | `ArrayType`, `ListType`, `Distinct`, `RequiredArrayKeys`, `BooleanType`, `IntegerType`, `StringType`, `Numeric`, `MinDigits`, `MaxDigits` | 3 days |
| STORY-001-008 | Database-backed constraint attributes | `Exists`, `Unique`, `CurrentPassword`, `MacAddress` | 2 days |

**Total estimated effort**: 26 days (approximately 5 developer-weeks).

**Sequencing**: STORY-001-000 must precede STORY-001-001. Every other story is independent and may be
worked in parallel or in any order. STORY-001-004, STORY-001-006 and STORY-001-007 additionally reach
into the documentation-records area, as each story notes.

### Final INVEST Re-validation

Every story below was checked against the following and passes:

- **Independent** — no story references another story's output; any single story can be the only one delivered.
- **Complete** — each covers recognition, documentation output, and failure behaviour for its family.
- **Valuable** — each closes a specific, nameable gap in the published API contract.
- **Estimable** — each is bounded by an explicit attribute list.
- **Right-sized** — 2 to 4 days, at most 3 core functional points.
- **Testable** — every acceptance criterion is observable in generated documentation without reading source code.

**Anti-patterns checked and avoided**: no story is split by technical layer; no single attribute is spread across stories; no story depends on another's implementation details.

---

## Story Files

- `requirements/[User-story-0]requirement-reconciliation-foundation.md`
- `requirements/[User-story-1]conditional-requirement-validation-attributes.md`
- `requirements/[User-story-2]prohibition-and-exclusion-validation-attributes.md`
- `requirements/[User-story-3]cross-field-comparison-validation-attributes.md`
- `requirements/[User-story-4]enumerated-value-validation-attributes.md`
- `requirements/[User-story-5]date-and-time-comparison-validation-attributes.md`
- `requirements/[User-story-6]file-and-upload-validation-attributes.md`
- `requirements/[User-story-7]array-and-type-assertion-validation-attributes.md`
- `requirements/[User-story-8]database-backed-validation-attributes.md`
