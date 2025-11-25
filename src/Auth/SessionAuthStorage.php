<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

use Compose\Http\Session\Session;
use Compose\Container\ResolvableInterface;

/**
 * Stores the current identity in the HTTP session.
 */
final class SessionAuthStorage implements AuthStorageInterface, ResolvableInterface
{
    private const SESSION_KEY = '__AUTH_IDENTITY__';

    public function __construct(
        private readonly Session $session
    ) {
    }

    public function store(Identity $identity): void
    {
        $this->session->set(self::SESSION_KEY, [
            'id' => $identity->getId(),
            'email' => $identity->getEmail(),
            'username' => $identity->getUsername(),
            'roles' => $identity->getRoles(),
            'profile' => $identity->getProfile(),
            'preferences' => $identity->getPreferences(),
        ]);
    }

    public function clear(): void
    {
        $this->session->unset(self::SESSION_KEY);
    }

    public function load(): ?Identity
    {
        $data = $this->session->get(self::SESSION_KEY);
        if (!is_array($data) || !isset($data['id'], $data['email'])) {
            return null;
        }

        return new Identity(
            id: (int) $data['id'],
            email: (string) $data['email'],
            username: $data['username'] ?? null,
            roles: is_array($data['roles'] ?? null) ? $data['roles'] : [],
            profile: is_array($data['profile'] ?? null) ? $data['profile'] : [],
            preferences: is_array($data['preferences'] ?? null) ? $data['preferences'] : [],
        );
    }
}
