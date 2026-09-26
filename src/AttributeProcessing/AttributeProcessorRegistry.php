<?php

namespace Abrha\LaravelDataDocs\AttributeProcessing;

use Abrha\LaravelDataDocs\AttributeProcessing\Processors\AcceptedIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\AcceptedProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\BetweenProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ConfirmedProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DateFormatProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DeclinedProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DescriptionProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DifferentProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsBetweenProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\DigitsProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EmailProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\EndsWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExampleProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithoutProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ExcludeWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\GreaterThanProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\InArrayProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanOrEqualToProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\LessThanProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MaxProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MinProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\MultipleOfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\PasswordProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitedUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\ProhibitsProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\QueryParameterProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\InProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\NotInProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RegexProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredIfProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredUnlessProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithAllProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutAllProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithoutProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\RequiredWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SameProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\SizeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\StartsWithProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\StaticAttributeProcessor;
use Abrha\LaravelDataDocs\AttributeProcessing\Processors\UrlProcessor;
use Abrha\LaravelDataDocs\Attributes\QueryParameter;
use Spatie\LaravelData\Attributes\Validation\Accepted;
use Spatie\LaravelData\Attributes\Validation\AcceptedIf;
use Spatie\LaravelData\Attributes\Validation\ActiveUrl;
use Spatie\LaravelData\Attributes\Validation\Alpha;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\AlphaNumeric;
use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Attributes\Validation\Date;
use Spatie\LaravelData\Attributes\Validation\DateFormat;
use Spatie\LaravelData\Attributes\Validation\Declined;
use Spatie\LaravelData\Attributes\Validation\DeclinedIf;
use Spatie\LaravelData\Attributes\Validation\Different;
use Spatie\LaravelData\Attributes\Validation\Digits;
use Spatie\LaravelData\Attributes\Validation\DigitsBetween;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Exclude;
use Spatie\LaravelData\Attributes\Validation\ExcludeIf;
use Spatie\LaravelData\Attributes\Validation\ExcludeUnless;
use Spatie\LaravelData\Attributes\Validation\ExcludeWith;
use Spatie\LaravelData\Attributes\Validation\ExcludeWithout;
use Spatie\LaravelData\Attributes\Validation\Filled;
use Spatie\LaravelData\Attributes\Validation\EndsWith;
use Spatie\LaravelData\Attributes\Validation\GreaterThan;
use Spatie\LaravelData\Attributes\Validation\GreaterThanOrEqualTo;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\InArray;
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
use Spatie\LaravelData\Attributes\Validation\NotIn;
use Spatie\LaravelData\Attributes\Validation\Password;
use Spatie\LaravelData\Attributes\Validation\Prohibited;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Attributes\Validation\ProhibitedUnless;
use Spatie\LaravelData\Attributes\Validation\Prohibits;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\RequiredUnless;
use Spatie\LaravelData\Attributes\Validation\RequiredWith;
use Spatie\LaravelData\Attributes\Validation\RequiredWithAll;
use Spatie\LaravelData\Attributes\Validation\RequiredWithout;
use Spatie\LaravelData\Attributes\Validation\RequiredWithoutAll;
use Spatie\LaravelData\Attributes\Validation\Same;
use Spatie\LaravelData\Attributes\Validation\Size;
use Spatie\LaravelData\Attributes\Validation\StartsWith;
use Spatie\LaravelData\Attributes\Validation\Ulid;
use Spatie\LaravelData\Attributes\Validation\Uppercase;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Abrha\LaravelDataDocs\Attributes\Description;
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

    /**
     * A subclass of a registered attribute is processed as its nearest
     * registered parent, as Laravel Data enforces it through the inherited rule.
     */
    public function getProcessorFor(string $attributeClass): ?AttributeProcessor
    {
        $parents = class_exists($attributeClass) ? array_values(class_parents($attributeClass)) : [];

        foreach ([$attributeClass, ...$parents] as $class) {
            if (isset($this->processors[$class])) {
                return $this->processors[$class];
            }
        }

        return null;
    }

    private function registerDefaults(): void
    {
        $this->register(Email::class, new EmailProcessor());
        $this->register(Url::class, new UrlProcessor());
        $this->register(ActiveUrl::class, new StaticAttributeProcessor(format: 'uri', description: 'Must be an active URL.'));
        $this->register(Uuid::class, new StaticAttributeProcessor(format: 'uuid', description: 'Must be a valid UUID.', valueRule: 'uuid'));
        $this->register(Password::class, new PasswordProcessor());
        $this->register(IPv4::class, new StaticAttributeProcessor(format: 'ipv4', description: 'Must be a valid IPv4 address.', valueRule: 'ipv4'));
        $this->register(IPv6::class, new StaticAttributeProcessor(format: 'ipv6', description: 'Must be a valid IPv6 address.', valueRule: 'ipv6'));
        $this->register(IP::class, new StaticAttributeProcessor(description: 'Must be a valid IP address.', exampleFormat: 'ipv4', valueRule: 'ip'));
        $this->register(Date::class, new DateProcessor());
        $this->register(Json::class, new StaticAttributeProcessor(format: 'json', description: 'Must be a valid JSON string.', valueRule: 'json'));
        $this->register(Ulid::class, new StaticAttributeProcessor(pattern: '^[0-7][0-9A-HJKMNP-TV-Za-hjkmnp-tv-z]{25}$', description: 'Must be a valid ULID.'));
        $this->register(Alpha::class, new StaticAttributeProcessor(pattern: '^[a-zA-Z]+$', description: 'Must contain only letters.', valuePattern: '^[\pL\pM]+$'));
        $this->register(AlphaDash::class, new StaticAttributeProcessor(pattern: '^[a-zA-Z0-9_-]+$', description: 'Must contain only letters, numbers, dashes, and underscores.', valuePattern: '^[\pL\pM\pN_-]+$'));
        $this->register(AlphaNumeric::class, new StaticAttributeProcessor(pattern: '^[a-zA-Z0-9]+$', description: 'Must contain only letters and numbers.', valuePattern: '^[\pL\pM\pN]+$'));
        $this->register(Lowercase::class, new StaticAttributeProcessor(pattern: '^[a-z]+$', description: 'Must contain only lowercase letters.', valuePattern: '^[^\p{Lu}\p{Lt}]*$'));
        $this->register(Uppercase::class, new StaticAttributeProcessor(pattern: '^[A-Z]+$', description: 'Must contain only uppercase letters.', valuePattern: '^[^\p{Ll}\p{Lt}]*$'));

        $this->register(DateFormat::class, new DateFormatProcessor());
        $this->register(Digits::class, new DigitsProcessor());
        $this->register(DigitsBetween::class, new DigitsBetweenProcessor());
        $this->register(StartsWith::class, new StartsWithProcessor());
        $this->register(EndsWith::class, new EndsWithProcessor());
        $this->register(Regex::class, new RegexProcessor());
        $this->register(In::class, new InProcessor());
        $this->register(NotIn::class, new NotInProcessor());
        $this->register(MultipleOf::class, new MultipleOfProcessor());

        $this->register(Min::class, new MinProcessor());
        $this->register(Max::class, new MaxProcessor());
        $this->register(Between::class, new BetweenProcessor());
        $this->register(Size::class, new SizeProcessor());

        $this->register(GreaterThan::class, new GreaterThanProcessor());
        $this->register(GreaterThanOrEqualTo::class, new GreaterThanOrEqualToProcessor());
        $this->register(LessThan::class, new LessThanProcessor());
        $this->register(LessThanOrEqualTo::class, new LessThanOrEqualToProcessor());

        $this->register(RequiredIf::class, new RequiredIfProcessor());
        $this->register(RequiredUnless::class, new RequiredUnlessProcessor());
        $this->register(RequiredWith::class, new RequiredWithProcessor());
        $this->register(RequiredWithAll::class, new RequiredWithAllProcessor());
        $this->register(RequiredWithout::class, new RequiredWithoutProcessor());
        $this->register(RequiredWithoutAll::class, new RequiredWithoutAllProcessor());
        $this->register(Filled::class, new StaticAttributeProcessor(description: 'When included, must not be empty.'));

        $this->register(Prohibited::class, new ProhibitedProcessor());
        $this->register(ProhibitedIf::class, new ProhibitedIfProcessor());
        $this->register(ProhibitedUnless::class, new ProhibitedUnlessProcessor());
        $this->register(Prohibits::class, new ProhibitsProcessor());
        $this->register(Exclude::class, new ExcludeProcessor());
        $this->register(ExcludeIf::class, new ExcludeIfProcessor());
        $this->register(ExcludeUnless::class, new ExcludeUnlessProcessor());
        $this->register(ExcludeWith::class, new ExcludeWithProcessor());
        $this->register(ExcludeWithout::class, new ExcludeWithoutProcessor());

        $this->register(Same::class, new SameProcessor());
        $this->register(Different::class, new DifferentProcessor());
        $this->register(InArray::class, new InArrayProcessor());
        $this->register(Confirmed::class, new ConfirmedProcessor());

        $this->register(Accepted::class, new AcceptedProcessor());
        $this->register(AcceptedIf::class, new AcceptedIfProcessor());
        $this->register(Declined::class, new DeclinedProcessor());
        $this->register(DeclinedIf::class, new DeclinedIfProcessor());

        $this->register(Example::class, new ExampleProcessor());
        $this->register(Description::class, new DescriptionProcessor());
        $this->register(QueryParameter::class, new QueryParameterProcessor());
    }
}
