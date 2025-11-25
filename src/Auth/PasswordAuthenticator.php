<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

use Compose\Container\ResolvableInterface;
use Compose\Web\Auth\Exception\InvalidCredentialsException;
use Compose\Web\Module\User\UserServiceInterface;

final class PasswordAuthenticator implements AuthenticatorInterface, ResolvableInterface
{
    public function __construct(
        private readonly UserServiceInterface $users,
        private readonly PasswordHasherInterface $hasher
    ) {
    }

    public function supports(Credential $credential): bool
    {
        return $credential->getType() === 'password';
    }

    public function authenticate(Credential $credential): Identity
    {
        $secret = $credential->getSecret();
        if ($secret === null || $secret === '') {
            throw new InvalidCredentialsException('Password is required.');
        }

        $user = $this->users->getByEmail($credential->getIdentifier());
        if ($user === null || !$user->isActive()) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if (!$this->hasher->verify($secret, $user->getPasswordHash())) {
            throw new InvalidCredentialsException('Invalid credentials.');
        }

        return new Identity(
            id: $user->getId(),
            email: $user->getEmail(),
            username: $user->getUsername(),
            roles: $user->getRoles(),
            profile: $user->getProfile(),
            preferences: $user->getPreferences(),
        );
    }
}
