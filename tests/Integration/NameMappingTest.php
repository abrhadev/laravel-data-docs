<?php

use Abrha\LaravelDataDocs\Pipeline\PipelineFactory;
use Abrha\LaravelDataDocs\Services\ParameterGenerator;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Confirmed;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Support\DataConfig;

function mappedParameterNames(string $class, bool $outputNames = false): array
{
    return array_keys((new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class), $outputNames))($class));
}

it('publishes request parameters under the names Laravel Data validates', function () {
    $validated = array_keys(NameMappingRequestData::getValidationRules([]));

    expect(mappedParameterNames(NameMappingRequestData::class))
        ->toBe(['first_name', 'mail', 'new_password', 'new_password_confirmation', 'home_address', 'home_address.street_name'])
        ->and($validated)->toContain('first_name', 'mail', 'new_password', 'home_address', 'home_address.street_name');
});

it('names the confirmation companion after the mapped input name', function () {
    $parameters = (new ParameterGenerator(PipelineFactory::createDefault(), app(DataConfig::class)))(NameMappingRequestData::class);

    expect($parameters['new_password']->description)->toContain('<b><i>new_password_confirmation</i></b>')
        ->and($parameters['new_password_confirmation']->description)->toStartWith('Must match the value of <b><i>new_password</i></b>.');
});

it('publishes response fields under the names Laravel Data writes', function () {
    $output = array_keys(NameMappingResponseData::from(['id' => 1, 'displayName' => 'A'])->toArray());

    expect(mappedParameterNames(NameMappingResponseData::class, outputNames: true))->toBe($output)
        ->and($output)->toBe(['id', 'display_name']);
});

it('publishes no confirmation companion for a response DTO', function () {
    expect(mappedParameterNames(NameMappingConfirmedResponseData::class, outputNames: true))->toBe(['password'])
        ->and(mappedParameterNames(NameMappingConfirmedResponseData::class))->toBe(['password', 'password_confirmation']);
});

it('publishes a mapped array of Data under the names Laravel Data validates for its items', function () {
    $published = mappedParameterNames(NameMappingListRequestData::class);
    $payload = ['user_list' => [['display_name' => 'A']]];
    $validated = array_keys(NameMappingListRequestData::getValidationRules($payload));

    expect($published)->toBe(['user_list', 'user_list[].display_name']);

    foreach ($published as $name) {
        $wildcard = str_replace('[]', '.*', $name);
        $indexed = str_replace('[]', '.0', $name);

        expect(in_array($wildcard, $validated, true) || in_array($indexed, $validated, true))
            ->toBeTrue("{$name} is not among the validated keys: " . implode(', ', $validated));
    }
});

class NameMappingListItemData extends Data
{
    public function __construct(
        #[MapInputName('display_name')]
        public string $displayName,
    ) {}
}

class NameMappingListRequestData extends Data
{
    public function __construct(
        /** @var NameMappingListItemData[] */
        #[MapInputName('user_list')]
        public array $users,
    ) {}
}

class NameMappingConfirmedResponseData extends Data
{
    public function __construct(
        #[Confirmed]
        public string $password,
    ) {}
}

#[MapName(SnakeCaseMapper::class)]
class NameMappingAddressData extends Data
{
    public function __construct(
        public string $streetName,
    ) {}
}

class NameMappingRequestData extends Data
{
    public function __construct(
        #[MapInputName('first_name')]
        public string $firstName,
        #[MapName('mail')]
        public string $email,
        #[MapInputName('new_password'), Confirmed]
        public string $newPassword,
        #[MapInputName('home_address')]
        public NameMappingAddressData $homeAddress,
    ) {}
}

class NameMappingResponseData extends Data
{
    public function __construct(
        public int $id,
        #[MapOutputName('display_name')]
        public string $displayName,
    ) {}
}
