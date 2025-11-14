<?php

declare(strict_types=1);

namespace Compose\Web\Validation\Validator;

use InvalidArgumentException;

final class NumberRange
{
    public function __construct(
        private readonly ?float $min = null,
        private readonly ?float $max = null,
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
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_int($value) && !is_float($value) && !is_numeric($value)) {
            return $this->message ?? 'Invalid number';
        }

        $number = (float) $value;

        if ($this->min !== null && $number < $this->min) {
            return $this->message ?? "Must be at least {$this->min}";
        }

        if ($this->max !== null && $number > $this->max) {
            return $this->message ?? "Must be at most {$this->max}";
        }

        return null;
    }
}
