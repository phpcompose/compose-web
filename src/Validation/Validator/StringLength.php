<?php

declare(strict_types=1);

namespace Compose\Web\Validation\Validator;

use InvalidArgumentException;
use Stringable;

final class StringLength
{
    public function __construct(
        private readonly ?int $min = null,
        private readonly ?int $max = null,
        private readonly ?string $message = null
    ) {
        if ($this->min === null && $this->max === null) {
            throw new InvalidArgumentException('Either min or max must be provided.');
        }

        if ($this->min !== null && $this->max !== null && $this->min > $this->max) {
            throw new InvalidArgumentException('Min cannot be greater than max.');
        }
    }

    public function __invoke(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = $this->toString($value);
        $length = $this->length($stringValue);

        if ($this->min !== null && $length < $this->min) {
            return $this->message ?? "Must be at least {$this->min} characters";
        }

        if ($this->max !== null && $length > $this->max) {
            return $this->message ?? "Must be at most {$this->max} characters";
        }

        return null;
    }

    private function length(string $value): int
    {
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }

    private function toString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        throw new InvalidArgumentException('Value is not stringable.');
    }
}
