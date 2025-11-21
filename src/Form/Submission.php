<?php

declare(strict_types=1);

namespace Compose\Web\Form;

use Compose\Web\Form\DTO\Field;
use Compose\Web\Validation\Result;

final class Submission
{
    /** @var array<string, Field> */
    private array $fieldMap = [];

    /**
     * @param Field[] $fields
     * @param array{name:string,value:string} $formIdField
     * @param array{name:string,value:string}|null $csrfField
     * @param string[] $submissionErrors
     */
    public function __construct(
        private readonly string $action,
        private readonly string $method,
        private readonly array $formIdField,
        private readonly ?array $csrfField,
        private readonly Result $result,
        private readonly array $fields,
        private readonly bool $submitted,
        private readonly array $submissionErrors = [],
    ) {
        foreach ($fields as $field) {
            $this->fieldMap[$field->name] = $field;
        }
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return array{name:string,value:string}
     */
    public function getFormIdField(): array
    {
        return $this->formIdField;
    }

    /**
     * @return array{name:string,value:string}|null
     */
    public function getCsrfField(): ?array
    {
        return $this->csrfField;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    public function isValid(): bool
    {
        return $this->result->isValid() && $this->submissionErrors === [];
    }

    public function isValidSubmit() : bool
    {
        return $this->isSubmitted() && $this->isValid();
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    /**
     * @return array<string, mixed>
     */
    public function getValues(): array
    {
        return $this->result->values;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRaw(): array
    {
        return $this->result->raw;
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->result->errors;
    }

    public function hasSubmissionErrors(): bool
    {
        return $this->submissionErrors !== [];
    }

    /**
     * @return string[]
     */
    public function getSubmissionErrors(): array
    {
        return $this->submissionErrors;
    }

    /**
     * @return string[]
     */
    public function getFieldErrors(string $name): array
    {
        return $this->result->errors[$name] ?? [];
    }

    /**
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    public function getField(string $name): ?Field
    {
        return $this->fieldMap[$name] ?? null;
    }
}
