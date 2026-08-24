---
alwaysApply: true
---

# Laravel Data Docs - Cursor Rules

## Code Style & Quality
- Never create explanatory comments in code - code must be self-documenting through clear naming and structure
- Always run ./jig pint on any edited PHP files before completing a task
- Maintain minimum 80% method coverage
- Follow existing naming patterns in the codebase

## Architecture Patterns
- **Pipeline Pattern**: Context objects flow through stages, each stage processes and returns modified context
- **Early Exit**: Implement early exit patterns for efficiency (e.g., stop pipeline if property is hidden)
- **Immutability**: Use readonly properties for metadata that shouldn't change after initialization
- **Value Objects**: Keep value objects immutable with readonly properties
- **Singleton Pattern**: Implement complete singleton with private constructor, private __clone(), and __wakeup() protection
- **Separation of Concerns**: Each pipeline stage should handle one specific responsibility

## Testing Requirements
- Run tests with './jig pest'
- Run tests with coverage: './jig coverage --min-method=80' (or use './jig quality' for stricter checks)
- Separate unit tests and integration tests into different directories (tests/Unit/, tests/Integration/)
- Create one test file per class (e.g., TypeStageTest.php for TypeStage.php)
- Unit tests must test classes in isolation, not through entry points
- Integration tests verify complete workflows through main services
- Remove skipped/legacy tests - don't leave them in the codebase
- When adding new functionality, always add corresponding tests

## File Organization
- Place files in correct module directories (src/Pipeline/, src/Services/, etc.)
- Unit tests mirror source structure: src/Pipeline/Stages/TypeStage.php → tests/Unit/Pipeline/Stages/TypeStageTest.php
- Always read 2-3 similar existing files before creating new files
- Follow existing file structure and patterns

## Docker Development Environment

This project uses [abrha/jig](https://github.com/abrhadev/jig). All development commands run through `./jig`. Docker Compose v2 is required (Linux or WSL2). On a fresh clone, `./jig` installs Composer dependencies if `vendor/` is missing.

### Command Execution
- All development commands run through `./jig`
- Use relative paths: `./jig pest` not `/home/user/path/jig pest`
- Do not use `./dev`, Sail, or host `vendor/bin/*` for package tooling
- Run `./jig doctor` if the environment looks wrong

### Key Commands
```bash
./jig pest                            # Run all tests
./jig pest tests/Unit/SomeTest.php    # Run specific test
./jig pint                            # Format all files
./jig pint src/                       # Format specific directory
./jig composer require package/name   # Install packages
./jig coverage --min-method=80        # Tests with coverage
./jig quality                         # Style, analysis, and tests
./jig shell                           # Interactive shell
./jig help                            # All available commands
```

## Workflow & Safety
- Always read existing files completely before editing them
- Never edit based on assumptions about file contents
- Never guess at solutions - search codebase for similar patterns first
- Preserve exact indentation and formatting of surrounding code
- When editing multiple related files, edit in logical dependency order (interfaces first, then implementations)
- Always use absolute paths when possible for tool arguments

## Error Handling
- If a command fails, immediately read relevant log files or error outputs before attempting fixes
- When encountering errors, provide specific details and reasoning rather than generic approaches
- Never leave code in a broken state
- If multiple approaches are possible, present options with pros/cons

## Project-Specific Patterns
- **ParameterContext**: Mutable context that flows through pipeline, accumulates metadata about properties
- **Pipeline Stages**: Implement ParameterPipelineStage interface, process context sequentially
- **Early Exit**: If context.isHidden is true, pipeline stops processing and returns immediately

## Testing Patterns
- Use PestPHP syntax for tests
- Test both positive and negative cases
- For pipeline stages, test with minimal context containing only required fields
- For integration tests, use real classes with attributes, not mocks
- Use descriptive test names that clearly explain what is being tested
- Follow Arrange-Act-Assert pattern: set up test data, execute the code, verify results
- Test behavior, not implementation details
- Keep tests independent with no shared state between tests
- Write fast and reliable tests that can run repeatedly
- Never add comments to tests - the test name should be self-explanatory

## Communication
- When presenting code changes, explain reasoning and potential impacts
- If multiple approaches exist, present options rather than picking arbitrarily
- Always provide exact file paths and line numbers when referencing code
- Before architectural changes, show current vs proposed structure for comparison
- Always give honest answers and suggestions; don't just agree with user
