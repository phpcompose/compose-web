<?php

declare(strict_types=1);

namespace Compose\Web\Validation\Validator;

final class EmailAddress
{
    public function __construct(private readonly ?string $message = null)
    {
    }

    public function __invoke(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = is_string($value) ? $value : (string) $value;
        if (filter_var($stringValue, FILTER_VALIDATE_EMAIL) !== false) {
            return null;
        }

        return $this->message ?? 'Invalid email address';
    }
}
