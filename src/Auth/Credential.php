<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

/**
 * Generic credential input used for authentication.
 *
 * Examples:
 *  - Password login: type=password, identifier=email, secret=password
 *  - OAuth login:    type=oauth, identifier=provider, extra contains tokens
 */
final class Credential
{
    /**
     * @param string $type Logical authenticator type (e.g., password, oauth)
     * @param string $identifier User-facing identifier (e.g., email or provider)
     * @param string|null $secret Secret for the credential (e.g., password)
     * @param array<string,mixed> $extra Additional data for provider-specific flows
     */
    public function __construct(
        private readonly string $type,
        private readonly string $identifier,
        private readonly ?string $secret = null,
        private readonly array $extra = [],
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * @return array<string,mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
