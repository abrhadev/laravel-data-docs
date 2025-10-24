<?php

namespace Abrha\LaravelDataDocs\ValueObjects;

final class Parameter
{
    public function __construct(
        public string $name,
        public string $type,
        public bool $required,
        public bool $nullable,
        public ParameterLocation $location,
        public string $description = '',
        public mixed $example = null,
        public ?array $enumValues = null,
        public array $openApiAttributes = []
    ) {}

    public function toArray(): array
    {
        $result = [
            'name'        => $this->name,
            'required'    => $this->required,
            'type'        => $this->type,
            'nullable'    => $this->nullable,
            'description' => $this->description,
            'example'     => $this->example,
        ];

        if ($this->enumValues !== null) {
            $result['enumValues'] = $this->enumValues;
        }

        if (!empty($this->openApiAttributes)) {
            $result['custom'] = [
                'openAPI' => $this->openApiAttributes,
            ];
        }

        return $result;
    }

    public function withLocation(ParameterLocation $location): self
    {
        return new self(
            $this->name,
            $this->type,
            $this->required,
            $this->nullable,
            $location,
            $this->description,
            $this->example,
            $this->enumValues,
            $this->openApiAttributes
        );
    }

    public function matchesLocation(ParameterLocation $location): bool
    {
        return $this->location === $location;
    }
}
