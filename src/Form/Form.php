<?php

declare(strict_types=1);

namespace Compose\Web\Form;

use Compose\Web\Form\DTO\Field;
use Compose\Web\Form\Submission;
use Compose\Web\Validation\Processor;
use Compose\Web\Validation\Result;
use Psr\Http\Message\ServerRequestInterface;

final class Form
{
    public const METHOD_POST = 'POST';
    public const METHOD_GET  = 'GET';
    public const FORM_KEY    = '__FORM_ID__';

    private string $id;
    private string $method;

    /** @var Field[] */
    private array $fields = [];
    /** @var array<string, Field> */
    private array $fieldMap = [];

    private ?Processor $processor = null;

    public function __construct(
        private readonly string $action = '',
        string $method = self::METHOD_POST
    ) {
        $normalized = strtoupper($method);
        $this->method = $normalized === self::METHOD_GET ? self::METHOD_GET : self::METHOD_POST;
        $this->id = md5($this->action . '-' . microtime(true) . '-' . random_int(PHP_INT_MIN, PHP_INT_MAX));
    }

    public function getId(): string
    {
        return $this->id;
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
        return ['name' => self::FORM_KEY, 'value' => $this->id];
    }

    public function isSubmitted(ServerRequestInterface $request): bool
    {
        $payload = $this->payload($request);
        $requestMethod = strtoupper($request->getMethod());

        return $this->isSubmittedWithPayload($payload, $requestMethod);
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

    public function addField(Field $field): self
    {
        $this->fields[] = $field;
        $this->fieldMap[$field->name] = $field;

        return $this;
    }

    /**
     * @param Field[] $fields
     */
    public function setFields(array $fields): self
    {
        $this->fields = [];
        $this->fieldMap = [];
        foreach ($fields as $field) {
            $this->addField($field);
        }

        return $this;
    }

    public function setProcessor(Processor $processor): self
    {
        $this->processor = $processor;
        return $this;
    }

    public function getProcessor(): Processor
    {
        return $this->processor ??= new Processor();
    }

    public function processRequest(ServerRequestInterface $request): Submission
    {
        $payload = $this->payload($request);
        $submitted = $this->isSubmittedWithPayload($payload, strtoupper($request->getMethod()));

        // Configure processor with current required fields
        $processor = $this->getProcessor();
        $required = [];
        foreach ($this->fields as $field) {
            if ($field->required) {
                $required[] = $field->name;
            }
        }
        if ($required) {
            $processor->setRequiredValues($required);
        }

        $result = $submitted
            ? $processor->process($payload)
            : new Result($payload, $payload);

        $fields = array_map(
            fn (Field $field) => $field->with([
                'value' => $result->values[$field->name] ?? $field->value,
                'errors' => $result->errors[$field->name] ?? [],
            ]),
            $this->getFields()
        );

        return new Submission(
            action: $this->action,
            method: $this->method,
            formIdField: $this->getFormIdField(),
            result: $result,
            fields: $fields,
            submitted: $submitted
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function payload(ServerRequestInterface $request): array
    {
        return $this->method === self::METHOD_GET
            ? $request->getQueryParams()
            : (array) $request->getParsedBody();
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function isSubmittedWithPayload(array $payload, string $requestMethod): bool
    {
        if ($requestMethod !== $this->method) {
            return false;
        }

        return ($payload[self::FORM_KEY] ?? null) === $this->id;
    }
}
