<?php

declare(strict_types=1);

namespace Compose\Web\Validation;

final class Result
{
    public function __construct(
        public array $raw,
        public array $values,
        public array $errors = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    public function addError(string $error, string $key): void
    {
        $this->errors[$key][] = $error;
    }
}
