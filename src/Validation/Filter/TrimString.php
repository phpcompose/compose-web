<?php

declare(strict_types=1);

namespace Compose\Web\Validation\Filter;

use Stringable;

/**
 * Trims stringable values while preserving nulls and non-string types.
 */
final class TrimString
{
    public function __construct(private readonly string $mask = " \t\n\r\0\x0B")
    {
    }

    public function __invoke(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return trim($value, $this->mask);
        }

        if (is_scalar($value) || $value instanceof Stringable) {
            return trim((string) $value, $this->mask);
        }

        return $value;
    }
}
