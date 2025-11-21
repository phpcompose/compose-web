<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

/**
 * Orchestrates authentication via pluggable authenticators and stores
 * the resulting identity.
 */
final class AuthService
{
    private ?Identity $current = null;

    /**
     * @param iterable<AuthenticatorInterface> $authenticators
     */
    public function __construct(
        private readonly AuthStorageInterface $storage,
        private readonly iterable $authenticators = [],
    ) {
        $this->current = $this->storage->load();
    }

    /**
     * @throws \RuntimeException when no authenticator supports the credential
     * @throws \Throwable from the underlying authenticator on failure
     */
    public function authenticate(Credential $credential): Identity
    {
        $authenticator = $this->resolveAuthenticator($credential);
        $identity = $authenticator->authenticate($credential);

        $this->current = $identity;
        $this->storage->store($identity);

        return $identity;
    }

    public function logout(): void
    {
        $this->current = null;
        $this->storage->clear();
    }

    public function hasIdentity(): bool
    {
        return $this->current !== null;
    }

    public function currentIdentity(): ?Identity
    {
        return $this->current;
    }

    private function resolveAuthenticator(Credential $credential): AuthenticatorInterface
    {
        foreach ($this->authenticators as $authenticator) {
            if ($authenticator->supports($credential)) {
                return $authenticator;
            }
        }

        throw new \RuntimeException(sprintf(
            'No authenticator configured for credential type "%s".',
            $credential->getType()
        ));
    }
}
