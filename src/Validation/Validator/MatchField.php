<?php

declare(strict_types=1);

namespace Compose\Web\Validation\Validator;

/**
 * Validates that the current field matches another field's value.
 */
final class MatchField
{
    public function __construct(
        private readonly string $otherKey,
        private readonly ?string $message = null
    ) {
    }

    public function __invoke(mixed $value, array $values): ?string
    {
        $other = $values[$this->otherKey] ?? null;

        if ($value === $other) {
            return null;
        }

        return $this->message ?? 'Values do not match.';
    }
}
