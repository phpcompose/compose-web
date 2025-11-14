<?php

declare(strict_types=1);

namespace Compose\Web\Form\DTO;

/**
 * Value object representing a single form field definition.
 */
final class Field
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly string $type = 'text',
        public readonly mixed $value = null,
        public readonly bool $required = false,
        public readonly array $errors = [],
        public readonly ?string $help = null,
        public readonly array $options = [],
        public readonly array $attributes = [],
        public readonly array $wrapperAttributes = [],
    ) {
    }

    /**
     * @param array{name:string,label:string,type?:string,value?:mixed,required?:bool,errors?:array,help?:string|null,options?:array,attributes?:array,wrapperAttributes?:array} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            label: $data['label'],
            type: $data['type'] ?? 'text',
            value: $data['value'] ?? null,
            required: (bool) ($data['required'] ?? false),
            errors: array_values($data['errors'] ?? []),
            help: $data['help'] ?? null,
            options: $data['options'] ?? [],
            attributes: $data['attributes'] ?? [],
            wrapperAttributes: $data['wrapperAttributes'] ?? [],
        );
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type,
            'value' => $this->value,
            'required' => $this->required,
            'errors' => $this->errors,
            'help' => $this->help,
            'options' => $this->options,
            'attributes' => $this->attributes,
            'wrapperAttributes' => $this->wrapperAttributes,
        ];
    }

    /**
     * @param array<string,mixed> $changes
     */
    public function with(array $changes): self
    {
        return new self(
            name: $changes['name'] ?? $this->name,
            label: $changes['label'] ?? $this->label,
            type: $changes['type'] ?? $this->type,
            value: $changes['value'] ?? $this->value,
            required: $changes['required'] ?? $this->required,
            errors: $changes['errors'] ?? $this->errors,
            help: $changes['help'] ?? $this->help,
            options: $changes['options'] ?? $this->options,
            attributes: $changes['attributes'] ?? $this->attributes,
            wrapperAttributes: $changes['wrapperAttributes'] ?? $this->wrapperAttributes,
        );
    }
}
