<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

use Compose\Container\ResolvableInterface;

final class PasswordHasher implements PasswordHasherInterface, ResolvableInterface
{
    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_DEFAULT);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
