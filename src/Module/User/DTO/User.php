<?php

declare(strict_types=1);

namespace Compose\Web\Module\User\DTO;

/**
 * Lightweight user DTO for authentication and profile access.
 */
final class User
{
    /**
     * @param array<string> $roles
     * @param array<string,mixed> $profile
     * @param array<string,mixed> $preferences
     */
    public function __construct(
        private readonly int $id,
        private readonly string $email,
        private readonly ?string $username,
        private readonly string $passwordHash,
        private readonly int $status,
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

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return $this->status === 1;
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
