# [STORY-001-006] File and Upload Attribute Documentation

**Estimated effort**: 4 days

### Background

Endpoints that accept uploads are documented today as if they accepted ordinary scalar values. Laravel Data expresses upload constraints through `#[File]`, `#[Image]`, `#[Mimes]`, `#[MimeTypes]`, `#[Dimensions]`, and `#[Size]`, and none of these reach the generated documentation.

The result is that an upload endpoint's contract is missing its most basic facts: that the field carries binary content at all, which file types are accepted, and how large a file may be. Consumers discover the accepted types by trial and error, and interactive documentation offers a text box where a file picker belongs. Upload limits are also the constraint most likely to differ between environments and to be asked about by integrators, which makes their absence from the contract expensive in support time.

Key points:

- **Business value and user needs**: Consumers need to know, before writing any code, whether a field takes a file, which formats are accepted, and what the size ceiling is.
- **Relationship with other features**: This is the first family in the decomposition that changes how a parameter's *type* is published, not only its description.
- **Why this capability is needed now**: Upload endpoints are disproportionately represented in integration support requests, and the documentation currently gives integrators nothing to work from.

### Business Value

- Provide **API consumers** with a correct upload contract — binary content, accepted file types, and size limits — so that upload integrations can be built from the documentation alone.
- Support **interactive API documentation**, where an upload field is presented as a file picker rather than a text input.
- Enable **support teams** to answer "why was my file rejected?" by pointing at the published contract instead of reading application source.

### Dependencies and Assumptions

- **Prerequisites**: None. This story is deliverable on its own.
- **Reaches beyond the attribute-translation layer**: publishing a parameter as a binary upload needs the parameter record to express something it cannot express today, so this story also touches the documentation-records area. Allow for that in planning.
- **Data assumptions**: Endpoints already expose Laravel Data objects as their request payload, and the package already produces parameter documentation for those objects' properties. Upload properties are declared on Data objects in the same way as any other property.
- **Integration points**: The generated documentation is consumed downstream as an OpenAPI description and as human-readable API reference pages. Binary parameters must be expressed in the form OpenAPI tooling recognises for file uploads.
- **Business constraints**: The package documents behaviour and must not change it. Server-level upload limits imposed outside the application are out of the package's knowledge and are not published by this story.

### Scope In

- Publishing a property constrained by `#[File]` or `#[Image]` as a binary upload parameter in the generated contract, rather than as a scalar.
- Documenting the accepted file types declared through `#[Mimes]` and `#[MimeTypes]`.
- Documenting upload size limits from `#[Size]` and image dimension limits from `#[Dimensions]`, each stated in units the reader can act on.
- Re-reading the already-supported `#[Min]` and `#[Max]` attributes as file-size limits when they appear on an upload property, rather than as value bounds.

### Scope Out

- Conditional requirement, prohibition, and comparison attributes — covered by STORY-001-001 through STORY-001-003.
- Server or infrastructure upload limits that are not declared as validation attributes.
- The behaviour of `#[Min]` and `#[Max]` on non-upload properties, which is already supported today and stays unchanged.
- Documenting multi-file upload collections beyond what is declared on the property itself.
- Any change to runtime validation or upload handling behaviour.

### Acceptance Criteria

#### AC1: A file property is published as a binary upload parameter

**Given** an endpoint accepts a Data object with a property `attachment` annotated `#[File]`
**When** documentation is generated for that endpoint
**Then** `attachment` is published as a binary upload parameter in a form that OpenAPI tooling recognises as a file
**And** it is not published as a plain string parameter

#### AC2: An image property is published as a binary upload and identified as an image

**Given** a property `avatar` annotated `#[Image]`
**When** documentation is generated
**Then** `avatar` is published as a binary upload parameter
**And** its description states that the value must be an image file

#### AC3: Accepted file extensions are documented

**Given** a property `document` annotated `#[Mimes(['pdf', 'docx'])]`
**When** documentation is generated
**Then** the description of `document` states that only `pdf` and `docx` files are accepted

#### AC4: Accepted media types are documented

**Given** a property `import` annotated `#[MimeTypes(['text/csv', 'application/json'])]`
**When** documentation is generated
**Then** the description of `import` states that only files of type `text/csv` or `application/json` are accepted

#### AC5: An upload size limit is documented in a unit the reader can act on

**Given** a property `attachment` annotated `#[File]` and `#[Max(5120)]`, where the limit is expressed in kilobytes
**When** documentation is generated
**Then** the description of `attachment` states the maximum upload size as `5 MB` or `5120 KB`
**And** the stated unit is explicit, so the number cannot be read as bytes

#### AC6: An exact size requirement is documented

**Given** a property `signature` annotated `#[File]` and `#[Size(100)]`
**When** documentation is generated
**Then** the description of `signature` states that the file must be exactly `100 KB`

#### AC7: Image dimension constraints are documented

**Given** a property `banner` annotated `#[Image]` and `#[Dimensions(minWidth: 1200, minHeight: 400)]`
**When** documentation is generated
**Then** the description of `banner` states that the image must be at least `1200` pixels wide and at least `400` pixels tall

#### AC8: An image ratio constraint is documented

**Given** a property `thumbnail` annotated `#[Image]` and `#[Dimensions(ratio: 1.0)]`
**When** documentation is generated
**Then** the description of `thumbnail` states that the image must have a `1:1` aspect ratio

#### AC9: Several upload constraints on one property produce one coherent description

**Given** a property `avatar` annotated with `#[Image]`, `#[Mimes(['jpg', 'png'])]`, and `#[Dimensions(maxWidth: 2000, maxHeight: 2000)]`
**When** documentation is generated
**Then** the description contains the image statement, the accepted-types statement, and the dimension statement
**And** the statements appear in a stable, repeatable order across successive documentation builds

#### AC10: A malformed or unrecognised attribute does not break the build

**Given** a property carries a `#[Dimensions]` constraint with no usable constraint values
**When** documentation is generated for the endpoint
**Then** documentation generation completes successfully
**And** every other property of that Data object is documented as normal

### Non-Functional Expectations

- Publishing a parameter as a binary upload must leave the rest of the generated contract valid for existing OpenAPI tooling, so that documentation viewers and client generators continue to work unchanged.
- Size limits must always be stated with an explicit unit, since an unqualified number is the single most common source of upload integration errors.
- Repeated documentation builds over an unchanged codebase produce identical output.
