<?php

declare(strict_types=1);

namespace Compose\Web\Form;

/**
 * Lightweight form metadata holder that generates a unique identifier per instance.
 */
final class Form
{
    public const METHOD_POST = 'POST';
    public const METHOD_GET = 'GET';
    public const FORM_KEY = '__FORM_ID__';

    private static int $counter = 0;

    private string $formId;
    private array $raw;
    private array $values;

    public function __construct(
        private string $action = '',
        private string $method = self::METHOD_POST,
        ?array $payload = null
    ) {
        $this->method = strtoupper($method) === self::METHOD_GET ? self::METHOD_GET : self::METHOD_POST;
        $this->raw = $payload ?? [];
        $this->values = $this->raw;
        $this->formId = md5($this->action . (++self::$counter));
    }

    public function getId(): string
    {
        return $this->formId;
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
     * Returns the last filtered/submitted values captured via isSubmitted().
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function setPayload(array $payload): void
    {
        $this->raw = $payload;
    }

    public function hiddenField(): array
    {
        return ['name' => self::FORM_KEY, 'value' => $this->formId];
    }

    public function isSubmitted(?array $payload = null, ?string $key = null): bool
    {
        $values = $payload ?? $this->raw;
        if (($values[self::FORM_KEY] ?? null) !== $this->formId) {
            return false;
        }

        $this->values = $values;

        if ($key !== null) {
            return array_key_exists($key, $values);
        }

        return true;
    }
}
