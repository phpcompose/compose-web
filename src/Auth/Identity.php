<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

/**
 * Immutable representation of an authenticated user.
 */
final class Identity
{
    /**
     * @param array<string> $roles
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $preferences
     */
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly ?string $username = null,
        private readonly array $roles = [],
        private readonly array $profile = [],
        private readonly array $preferences = [],
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<string,mixed>
     */
    public function getProfile(): array
    {
        return $this->profile;
    }

    /**
     * @return array<string,mixed>
     */
    public function getPreferences(): array
    {
        return $this->preferences;
    }
}
