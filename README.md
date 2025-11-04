# Laravel Data Docs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/abrha/laravel-data-docs.svg?style=flat-square)](https://packagist.org/packages/abrha/laravel-data-docs)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/abrhadev/laravel-data-docs/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/abrhadev/laravel-data-docs/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/abrhadev/laravel-data-docs/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/abrhadev/laravel-data-docs/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/abrha/laravel-data-docs.svg?style=flat-square)](https://packagist.org/packages/abrha/laravel-data-docs)

Generate beautiful API documentation automatically from [Spatie's Laravel Data](https://spatie.be/docs/laravel-data/) classes. This package integrates seamlessly with [Knuckles Scribe](https://scribe.knuckles.wtf/) to extract request and response schemas directly from your Data DTOs, eliminating manual documentation of API parameters.

## Features

- **Automatic Parameter Extraction**: Automatically generates API documentation from Laravel Data classes
- **Smart Attributes**: Control documentation with `#[Hidden]`, `#[Example]`, `#[QueryParameter]`, and `#[ResponseData]` attributes
- **Laravel Data Validation Support**: Automatic OpenAPI documentation for 30+ built-in Laravel Data validation attributes (`#[Email]`, `#[Min]`, `#[Max]`, `#[Uuid]`, etc.)
- **Nested Objects Support**: Handles nested Data objects and arrays with automatic dot notation and array notation
- **Type-Safe**: Leverages PHP 8.1+ attributes and Laravel Data's type system
- **Extensible Architecture**: Pipeline-based processing with custom stages, attribute processors, and type processors
- **Scribe Integration**: Works seamlessly with Knuckles Scribe for OpenAPI/Swagger documentation
- **Zero Configuration**: Works out of the box with sensible defaults

## Installation

You can install the package via composer:

```bash
composer require abrha/laravel-data-docs
```

### Scribe Configuration

Register the package strategies in your `config/scribe.php`:

```php
return [
    // ... other config

    'openapi' => [
        'enabled' => true,
        
        // IMPORTANT: Add the extended OpenAPI generator to properly merge
        // validation rules, formats, patterns, and other OpenAPI properties
        // extracted from Laravel Data attributes into the final specification
        'generators' => [
            \Abrha\LaravelDataDocs\OpenApi\ExtendedOpenApiGenerator::class,
        ],
    ],

    'strategies' => [
        'bodyParameters' => [
            \Abrha\LaravelDataDocs\Strategies\BodyParameters\GetFromRequestDTOStrategy::class,
            // ... other strategies
        ],
        'queryParameters' => [
            \Abrha\LaravelDataDocs\Strategies\QueryParameters\GetFromRequestDTOStrategy::class,
            // ... other strategies
        ],
        'responseFields' => [
            \Abrha\LaravelDataDocs\Strategies\ResponseDataStrategy::class,
            // ... other strategies
        ],
    ],
];
```

Optionally, publish the config file:

```bash
php artisan vendor:publish --tag="laravel-data-docs-config"
```

## Basic Usage

### 1. Create Your Data Classes

```php
use Spatie\LaravelData\Data;
use Abrha\LaravelDataDocs\Attributes\Example;
use Abrha\LaravelDataDocs\Attributes\Hidden;

class CreateUserRequest extends Data
{
    public function __construct(
        #[Example('john.doe@example.com')]
        public string $email,
        
        #[Example('John Doe')]
        public string $name,
        
        public int $age,
        
        #[Hidden]
        public bool $isAdmin = false,
    ) {}
}

class UserResponse extends Data
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public int $age,
        public string $created_at,
    ) {}
}
```

### 2. Use in Your Controllers

```php
use Abrha\LaravelDataDocs\Attributes\ResponseData;

class UserController extends Controller
{
    #[ResponseData(UserResponse::class)]
    public function store(CreateUserRequest $request)
    {
        $user = User::create($request->all());
        
        return UserResponse::from($user);
    }
    
    #[ResponseData(UserResponse::class)]
    public function show(User $user)
    {
        return UserResponse::from($user);
    }
}
```

### 3. Generate Documentation

```bash
php artisan scribe:generate
```

That's it! Scribe will automatically extract request parameters from `CreateUserRequest` and response structure from `UserResponse`.

## Available Attributes

The package automatically processes Laravel Data validation attributes to generate comprehensive OpenAPI documentation with validation rules, formats, and descriptions.

### `#[Hidden]`

Excludes a property from generated documentation. Useful for internal flags, metadata, or sensitive fields.

```php
use Abrha\LaravelDataDocs\Attributes\Hidden;

class UserData extends Data
{
    public function __construct(
        public string $name,
        
        #[Hidden]
        public bool $internalFlag = false,
        
        #[Hidden]
        public ?string $debugInfo = null,
    ) {}
}
```

### `#[Example]`

Provides custom example values for API documentation instead of auto-generated examples.

```php
use Abrha\LaravelDataDocs\Attributes\Example;

class ProductData extends Data
{
    public function __construct(
        #[Example('Premium Widget')]
        public string $name,
        
        #[Example(29.99)]
        public float $price,
        
        #[Example(['electronics', 'gadgets'])]
        public array $tags,
        
        #[Example(true)]
        public bool $inStock,
    ) {}
}
```

### `#[QueryParameter]`

Marks a property as a URL query parameter (for non-GET requests). GET requests treat all parameters as query parameters by default.

```php
use Abrha\LaravelDataDocs\Attributes\QueryParameter;

class SearchRequest extends Data
{
    public function __construct(
        #[QueryParameter]
        public string $search,
        
        #[QueryParameter]
        public int $page = 1,
        
        #[QueryParameter]
        public int $perPage = 15,
        
        // This will be a body parameter
        public array $filters = [],
    ) {}
}
```

### `#[ResponseData]`

Specifies the response DTO class for a controller method. Applied to controller methods, not Data classes.

```php
use Abrha\LaravelDataDocs\Attributes\ResponseData;

class PostController extends Controller
{
    #[ResponseData(PostResponse::class)]
    public function index()
    {
        return PostResponse::collection(Post::paginate());
    }
    
    #[ResponseData(PostResponse::class)]
    public function store(CreatePostRequest $request)
    {
        $post = Post::create($request->all());
        return PostResponse::from($post);
    }
}
```

### Laravel Data Validation Attributes

The package has built-in support for Laravel Data validation attributes. These automatically add OpenAPI validation rules, formats, and descriptions:

**Format Attributes:**
- `#[Email]` - Adds format: email
- `#[Url]`, `#[ActiveUrl]` - Adds format: uri
- `#[Uuid]` - Adds format: uuid
- `#[Password]` - Adds format: password
- `#[IPv4]`, `#[IPv6]`, `#[IP]` - Adds IP format validation
- `#[Date]` - Adds format: date
- `#[DateFormat]` - Adds custom date format pattern
- `#[Json]` - Adds format: json

**String Pattern Attributes:**
- `#[Alpha]` - Only letters
- `#[AlphaNumeric]` - Letters and numbers
- `#[AlphaDash]` - Letters, numbers, dashes, underscores
- `#[Lowercase]` - Lowercase letters only
- `#[Uppercase]` - Uppercase letters only
- `#[Ulid]` - ULID pattern validation
- `#[Regex]` - Custom regex pattern
- `#[StartsWith]`, `#[EndsWith]` - String prefix/suffix validation

**Numeric Validation:**
- `#[Min]`, `#[Max]` - Minimum/maximum values
- `#[Between]` - Value between min and max
- `#[GreaterThan]`, `#[GreaterThanOrEqualTo]` - Comparison validation
- `#[LessThan]`, `#[LessThanOrEqualTo]` - Comparison validation
- `#[MultipleOf]` - Must be multiple of value
- `#[Digits]` - Exact number of digits
- `#[DigitsBetween]` - Digits between min and max

**Example:**

```php
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Uuid;

class CreateUserRequest extends Data
{
    public function __construct(
        #[Email]
        public string $email,
        
        #[Min(3), Max(50)]
        public string $name,
        
        #[Min(18), Max(120)]
        public int $age,
        
        #[Uuid]
        public string $organizationId,
    ) {}
}
```

This automatically generates OpenAPI documentation with:
- Email format validation
- Name length constraints (3-50 characters)
- Age numeric constraints (18-120)
- UUID format for organizationId

## Advanced Features

### Nested Data Objects

The package automatically handles nested Data objects with dot notation:

```php
class AddressData extends Data
{
    public function __construct(
        public string $street,
        public string $city,
        public string $zipCode,
    ) {}
}

class UserData extends Data
{
    public function __construct(
        public string $name,
        public AddressData $address,
    ) {}
}
```

Generated parameters:
- `name` (string)
- `address` (object)
- `address.street` (string)
- `address.city` (string)
- `address.zipCode` (string)

### Arrays of Data Objects

Arrays of Data objects are automatically handled with array notation:

```php
class OrderItemData extends Data
{
    public function __construct(
        public int $productId,
        public int $quantity,
        public float $price,
    ) {}
}

class OrderData extends Data
{
    public function __construct(
        public string $customerName,
        
        /** @var OrderItemData[] */
        public array $items,
    ) {}
}
```

Generated parameters:
- `customerName` (string)
- `items` (object[])
- `items[].productId` (integer)
- `items[].quantity` (integer)
- `items[].price` (number)

## Extending Functionality

The package provides four extension methods, ordered from simplest to most advanced:

### 1. Config File (Simplest)

The easiest way to customize documentation is through the config file. Define custom type mappings without writing any code:

```php
// config/data-docs.php
return [
    'custom_types' => [
        App\ValueObjects\UUID::class => [
            'type' => 'string',
            'descriptions' => ['A valid UUID v4 identifier'],
            'pattern' => '^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$',
            'format' => 'uuid',
        ],
        
        App\ValueObjects\PhoneNumber::class => [
            'type' => 'string',
            'descriptions' => ['International phone number in E.164 format'],
            'pattern' => '^\+[1-9]\d{1,14}$',
            'minLength' => 10,
            'maxLength' => 15,
        ],
    ],
];
```

**Available configuration fields:**
- `type`: OpenAPI type (string, integer, number, boolean, array, object)
- `descriptions`: Array of description strings
- `pattern`: Regex pattern for validation
- `format`: OpenAPI format (uuid, email, date-time, etc.)
- `minimum`: Minimum value for numbers
- `maximum`: Maximum value for numbers
- `exclusiveMinimum`: Exclusive minimum value
- `exclusiveMaximum`: Exclusive maximum value
- `minLength`: Minimum string length
- `maxLength`: Maximum string length
- `minItems`: Minimum array items
- `maxItems`: Maximum array items
- `multipleOf`: Number must be multiple of this value

**When to use:** Simple type mappings where you just need to specify OpenAPI properties declaratively.

### 2. Custom Type Processors

For more dynamic control over type processing, implement a `CustomTypeProcessor`:

```php
use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

class UlidProcessor implements CustomTypeProcessor
{
    public function process(string $className, ParameterContext $context): void
    {
        $context->type = 'string';
        $context->descriptions = ['A valid ULID identifier'];
        $context->example = '01ARZ3NDEKTSV4RRFFQ69G5FAV';
        
        // You can add dynamic logic here
        // Access other context properties, generate examples, etc.
    }
}
```

Register in your `AppServiceProvider`:

```php
use Abrha\LaravelDataDocs\CustomTypeProcessing\CustomTypeProcessorRegistry;
use Symfony\Component\Uid\Ulid;

public function register()
{
    $registry = CustomTypeProcessorRegistry::getInstance();
    $registry->register(Ulid::class, new UlidProcessor());
}
```

**When to use:** When you need programmatic control over type documentation or need to generate dynamic examples/descriptions based on the class itself.

### 3. Custom Attribute Processors

Process custom or existing validation attributes by implementing an `AttributeProcessor`. This allows you to create reusable attributes that modify documentation behavior.

**Step 1:** Create your custom attribute:

```php
use Abrha\LaravelDataDocs\Attributes\DataDocsAttribute;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class InEnumCases implements DataDocsAttribute
{
    public function __construct(
        public readonly string $enumClass,
    ) {}
}
```

**Step 2:** Create the processor:

```php
use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessor;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

class InEnumCasesProcessor implements AttributeProcessor
{
    public function process(object $attribute, ParameterContext $context): void
    {
        $enumClass = $attribute->enumClass;
        $cases = array_map(fn($case) => $case->value, $enumClass::cases());
        
        $context->descriptions[] = 'Must be one of: ' . implode(', ', $cases);
        $context->example = $cases[0] ?? null;
        
        // You can modify any aspect of the context
        // Add to descriptions, set examples, modify type, etc.
    }
}
```

**Step 3:** Register in your `AppServiceProvider`:

```php
use Abrha\LaravelDataDocs\AttributeProcessing\AttributeProcessorRegistry;

public function register()
{
    $attributeRegistry = AttributeProcessorRegistry::getInstance();
    $attributeRegistry->register(InEnumCases::class, new InEnumCasesProcessor());
}
```

**Step 4:** Use in your Data classes:

```php
use App\Enums\UserStatus;

class UserData extends Data
{
    public function __construct(
        public string $name,
        
        #[InEnumCases(UserStatus::class)]
        public string $status,
    ) {}
}
```

**When to use:** When you need reusable, attribute-based validation logic that can be applied to multiple properties across different Data classes. Great for domain-specific validations.

### 4. Custom Pipeline Stages (Most Advanced)

For complete control over the processing pipeline, implement custom stages. This gives you access to the full context and allows modification at any point in the pipeline.

```php
use Abrha\LaravelDataDocs\Pipeline\ParameterPipelineStage;
use Abrha\LaravelDataDocs\Pipeline\Context\ParameterContext;

class CustomValidationStage implements ParameterPipelineStage
{
    public function process(ParameterContext $context): ParameterContext
    {
        // Full access to context - can modify any aspect
        $attributes = $context->propertyAttrs;
        
        // Complex custom logic here
        if (isset($attributes[Deprecated::class])) {
            $deprecated = $attributes[Deprecated::class];
            $context->descriptions[] = "**DEPRECATED**: {$deprecated->reason}";
        }
        
        // You can even stop processing by setting isHidden
        if ($this->shouldHideProperty($context)) {
            $context->isHidden = true;
        }
        
        return $context;
    }
    
    private function shouldHideProperty(ParameterContext $context): bool
    {
        // Complex hiding logic based on multiple factors
        return false;
    }
}
```

Register your stage in the pipeline:

```php
use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;

// Note: You'll need to customize how you create the pipeline
// This typically happens in a service provider or strategy
$pipeline = PipelineFactory::createDefault()
    ->addStage(new CustomValidationStage());
```

**When to use:** When you need to:
- Implement complex cross-cutting concerns
- Modify processing behavior based on multiple factors
- Add entirely new processing steps to the pipeline
- Have full control over the documentation generation flow

**Note:** This requires deeper understanding of the package internals and how the pipeline processes contexts.

## Extension Method Summary

| Method | Complexity | Use Case |
|--------|-----------|----------|
| **Config File** | ⭐ Simple | Static type mappings |
| **Type Processor** | ⭐⭐ Moderate | Dynamic type-specific logic |
| **Attribute Processor** | ⭐⭐⭐ Moderate-Advanced | Reusable attribute-based validation |
| **Pipeline Stage** | ⭐⭐⭐⭐ Advanced | Complex cross-cutting concerns |

## How It Works

### Pipeline Architecture

The package uses a pipeline pattern to process each property in your Data classes:

1. **Context Creation**: A `ParameterContext` object is created for each property
2. **Pipeline Processing**: The context flows through multiple stages:
   - `HiddenStage`: Checks for `#[Hidden]` attribute
   - `TypeStage`: Determines base type from PHP type hints
   - `CustomTypeStage`: Applies custom type configurations
   - `AttributeProcessingStage`: Processes all attributes using registered AttributeProcessors (includes 30+ built-in Laravel Data validation attributes)
   - `TypeDescriptionStage`: Generates type descriptions
   - `DefaultValueStage`: Extracts default values
   - `DefaultValueDescriptionStage`: Generates default value descriptions
   - `RequiredStage`: Determines if field is required
   - `ExampleGenerationStage`: Generates example values using Faker
3. **Parameter Generation**: Context is converted to API parameter format
4. **OpenAPI Enhancement**: The ExtendedOpenApiGenerator merges additional OpenAPI schema information into the final specification

### Scribe Integration

The package provides three Scribe strategies and an OpenAPI generator:

- **BodyParameters Strategy**: Extracts request body parameters from Data classes
- **QueryParameters Strategy**: Extracts query parameters (GET requests or properties marked with `#[QueryParameter]`)
- **ResponseData Strategy**: Extracts response structure from Data classes marked with `#[ResponseData]`
- **ExtendedOpenApiGenerator**: Merges custom OpenAPI schema information (default values, formats, patterns, etc.) extracted from Laravel Data attributes into the generated OpenAPI specification

These strategies automatically detect Data classes in your controller methods and generate comprehensive documentation with validation rules.

## Development

This project uses a Docker-based development environment with a long-running container for fast command execution.

### Getting Started

Start the development container (do this once):

```bash
./dev up
```

The container stays running in the background. All subsequent commands execute instantly without container startup overhead.

### Available Commands

```bash
./dev pest                             # Run all tests
./dev pest tests/Unit/SomeTest.php     # Run specific test
./dev pint                             # Format all files
./dev pint src/                        # Format specific directory
./dev composer require package/name    # Install packages
./dev shell                            # Interactive shell
./dev down                             # Stop container when done
```

Run `./dev help` to see all available commands.

## Testing

Run all tests:

```bash
./dev pest
```

Run tests with coverage:

```bash
./dev coverage
```

Or using composer:

```bash
composer test-coverage
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Hamed Ehtesham](https://github.com/hamed-ehtesham)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
