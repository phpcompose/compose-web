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
    ) {
    }

    /**
     * @param array{name:string,label:string,type?:string,value?:mixed,required?:bool,errors?:array,help?:string|null,options?:array,attributes?:array} $data
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
        );
    }

    /**
     * @param array<int, array<string,mixed>> $definitions
     * @return Field[]
     */
    public static function createMany(array $definitions): array
    {
        return array_map(static fn (array $definition): self => self::fromArray($definition), $definitions);
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
        ];
    }

    /**
     * @param array<string,mixed> $changes
     */
    public function with(array $changes): self
    {
        return new self(
            name: array_key_exists('name', $changes) ? $changes['name'] : $this->name,
            label: array_key_exists('label', $changes) ? $changes['label'] : $this->label,
            type: array_key_exists('type', $changes) ? $changes['type'] : $this->type,
            value: array_key_exists('value', $changes) ? $changes['value'] : $this->value,
            required: array_key_exists('required', $changes) ? $changes['required'] : $this->required,
            errors: array_key_exists('errors', $changes) ? $changes['errors'] : $this->errors,
            help: array_key_exists('help', $changes) ? $changes['help'] : $this->help,
            options: array_key_exists('options', $changes) ? $changes['options'] : $this->options,
            attributes: array_key_exists('attributes', $changes) ? $changes['attributes'] : $this->attributes,
        );
    }
}
