# Custom Type Extension Point

Core canvas. Owns `src/CustomTypeProcessing/**`.

Related canvases: parameter metadata pipeline (its `CustomTypeStage` calls the registry after config lookup); documentation records (`CustomTypeConfig` is not this module).

## Requirements

- Allow replacing documentation for a PHP class name that is not a standard OpenAPI scalar/object/array type, without editing CustomTypeStage.
- Provide a process-wide registry of class name to processor.
- Ship with no built-in class processors.

## Entities

```mermaid
classDiagram
    class CustomTypeProcessor {
        <<interface>>
        +process(string className, ParameterContext context) void
    }

    class CustomTypeProcessorRegistry {
        -processors array
        +getInstance() CustomTypeProcessorRegistry$
        +register(string className, CustomTypeProcessor processor) void
        +getProcessorFor(string className) CustomTypeProcessor~
    }

    CustomTypeProcessorRegistry --> CustomTypeProcessor : maps class name
```

`ParameterContext` is owned by the parameter metadata pipeline canvas. This module only mutates it through `CustomTypeProcessor::process`.

## Approach

Singleton service locator, same pattern as `AttributeProcessorRegistry`: private constructor, private clone, `__wakeup` throws, `getInstance` lazy-creates. Default registration is an empty method. Config-based custom types are applied in CustomTypeStage *before* this registry and are out of scope here.

Known divergences:

- `registerDefaults` is empty; the extension point exists but the package registers nothing.
- No unregister, no overwrite policy documented; `register` overwrites the same class key.
- Processors receive the class name string that was in `context->type`, which may not be a real class.

## Structure

`src/CustomTypeProcessing/`: interface plus singleton registry. CustomTypeStage is the only in-package caller.

## Operations

### CustomTypeProcessor

- Method `process(string $className, ParameterContext $context): void`. Implementations may set any writable context field (`name` and `property` are readonly). Attribute processors run afterwards and may overwrite what they set, and a `pattern` they set is published verbatim, so it must already be an ECMA-262 regex without delimiters; when the processor left `valuePatterns` empty, `CustomTypeStage` also makes it the field's only entry there, so it filters `#[In]` values as a config `pattern` does; value patterns a processor sets itself (Laravel's exact rule beside an approximate `pattern`) are kept. `CustomTypeStageTest` pins both and, through the default pipeline, a processor pattern narrowing an `#[In]` set. No return value; stage does not inspect success.

### CustomTypeProcessorRegistry

- `final` class.
- Private constructor calls `registerDefaults()`.
- Private `__clone`.
- `__wakeup` throws Exception `Cannot unserialize singleton`.
- `getInstance()` returns the same instance for the process.
- `register(string $className, CustomTypeProcessor $processor)` stores by exact class-name key.
- `getProcessorFor(string $className)` returns the processor or null; no inheritance/interface matching.
- `registerDefaults()` currently registers nothing.

## Norms

- Namespace `Abrha\LaravelDataDocs\CustomTypeProcessing`.
- Singleton with unserialize guard throwing `\Exception`, not a domain exception type.
- Map lookup is exact string match on `context->type`. `Foo[]` (an array of `Foo`, as `TypeStage` writes it) is a distinct key: document arrays of `Foo` by registering a processor for `Foo[]` too, which sets an array type and an array example (it receives `$className`, so one processor can branch on the `[]` suffix). Without a `Foo[]` processor or config entry, the field is published as `string[]` with "Each item must be a Foo." (parameter metadata pipeline canvas).
- A processor must set `type` to one of `CustomTypeStage`'s standard types (`string`, `integer`, `number`, `boolean`, `object`, or one of them with `[]`); any other value, including the class name, `array` and `file`, is published as `string`, or `string[]` for an array key, which also gets "Each item must be a {base}.", as the no-processor fallback does, unless the processor already wrote that sentence (parameter metadata pipeline canvas). A scalar key rewritten to `string` gets no class sentence: the processor describes the type. README lists the same set and names the class name, `array` and `file` as rewritten. On an array type the processor's `pattern`, `format` and length and numeric bounds are set aside as the item schema and published under `items` (parameter metadata pipeline and package and OpenAPI glue canvases). An array type's item constraints also decide which `#[In]` item values are published and shape each item of the generated example; the item `pattern` decides them only through the value patterns (the processor's own, else the `pattern` itself), and an approximate item `pattern` that a published `#[In]` item rejects is left out of `items` (parameter metadata and example generation canvases; `CustomTypeStageTest` "leaves out an approximate item pattern…").

## Safeguards

- Missing processor is null; CustomTypeStage then coerces type to `string`, or `string[]` for an array key (that fallback is pipeline-owned).
- Registry is process-global; tests that register processors can leak across cases if the singleton is not reset (no reset API exists).
- No validation that `$className` is an existing class.
