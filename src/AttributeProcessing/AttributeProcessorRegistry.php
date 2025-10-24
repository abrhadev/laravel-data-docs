<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\BetweenProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateFormatProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsBetweenProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EndsWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExampleProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MaxProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MinProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MultipleOfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\QueryParameterProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RegexProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SizeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\StartsWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\StaticAttributeProcessor;
use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Spatie\LaravelData\Attributes\Validation\ActiveUrl;
use Spatie\LaravelData\Attributes\Validation\Alpha;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\AlphaNumeric;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\EndsWith;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\IP;
use Spatie\LaravelData\Attributes\Validation\IPv4;
use Spatie\LaravelData\Attributes\Validation\IPv6;
use Spatie\LaravelData\Attributes\Validation\Json;
use Spatie\LaravelData\Attributes\Validation\LessThan;
use Spatie\LaravelData\Attributes\Validation\LessThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\Lowercase;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\MultipleOf;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Attributes\Validation\Uppercase;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Abrha\LaravelDataDocs\Attributes\Example;

final class AttributeProcessorRegistry
{
    private static ?self $instance = null;

    private array $processors = [];

    private function __construct()
    {
        $this->registerDefaults();
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \Exception('Cannot unserialize singleton');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function register(string $attributeClass, AttributeProcessor $processor): void
    {
        $this->processors[$attributeClass] = $processor;
    }

    public function getProcessorFor(string $attributeClass): ?AttributeProcessor
    {
        return $this->processors[$attributeClass] ?? null;
    }

    private function registerDefaults(): void
    {
        $this->register(Email::class, new StaticAttributeProcessor(format: 'email', description: 'Must be a valid email address.'));
        $this->register(Url::class, new StaticAttributeProcessor(format: 'uri', description: 'Must be a valid URL.'));
        $this->register(ActiveUrl::class, new StaticAttributeProcessor(format: 'uri', description: 'Must be an active URL.'));
        $this->register(Uuid::class, new StaticAttributeProcessor(format: 'uuid', description: 'Must be a valid UUID.'));
        $this->register(Password::class, new StaticAttributeProcessor(format: 'password', description: 'Must be a valid password.'));
        $this->register(IPv4::class, new StaticAttributeProcessor(format: 'ipv4', description: 'Must be a valid IPv4 address.'));
        $this->register(IPv6::class, new StaticAttributeProcessor(format: 'ipv6', description: 'Must be a valid IPv6 address.'));
        $this->register(IP::class, new StaticAttributeProcessor(description: 'Must be a valid IP address.'));
        $this->register(Date::class, new StaticAttributeProcessor(format: 'date', description: 'Must be a valid date.'));
        $this->register(Json::class, new StaticAttributeProcessor(format: 'json', description: 'Must be a valid JSON string.'));
        $this->register(Ulid::class, new StaticAttributeProcessor(pattern: '^[0-9A-HJKMNP-TV-Z]{26}$', description: 'Must be a valid ULID.'));
        $this->register(Alpha::class, new StaticAttributeProcessor(pattern: '^[a-zA-Z]+$', description: 'Must contain only letters.'));
        $this->register(AlphaDash::class, new StaticAttributeProcessor(pattern: '^[a-zA-Z0-9_-]+$', description: 'Must contain only letters, numbers, dashes, and underscores.'));
        $this->register(AlphaNumeric::class, new StaticAttributeProcessor(pattern: '^[a-zA-Z0-9]+$', description: 'Must contain only letters and numbers.'));
        $this->register(Lowercase::class, new StaticAttributeProcessor(pattern: '^[a-z]+$', description: 'Must contain only lowercase letters.'));
        $this->register(Uppercase::class, new StaticAttributeProcessor(pattern: '^[A-Z]+$', description: 'Must contain only uppercase letters.'));

        $this->register(DateFormat::class, new DateFormatProcessor());
        $this->register(Digits::class, new DigitsProcessor());
        $this->register(DigitsBetween::class, new DigitsBetweenProcessor());
        $this->register(StartsWith::class, new StartsWithProcessor());
        $this->register(EndsWith::class, new EndsWithProcessor());
        $this->register(Regex::class, new RegexProcessor());
        $this->register(MultipleOf::class, new MultipleOfProcessor());

        $this->register(Min::class, new MinProcessor());
        $this->register(Max::class, new MaxProcessor());
        $this->register(Between::class, new BetweenProcessor());
        $this->register(Size::class, new SizeProcessor());

        $this->register(GreaterThan::class, new GreaterThanProcessor());
        $this->register(GreaterThanOrEqualTo::class, new GreaterThanOrEqualToProcessor());
        $this->register(LessThan::class, new LessThanProcessor());
        $this->register(LessThanOrEqualTo::class, new LessThanOrEqualToProcessor());

        $this->register(Example::class, new ExampleProcessor());
        $this->register(QueryParameter::class, new QueryParameterProcessor());
    }
}
