<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

interface AuthenticatorInterface
{
    /**
     * Whether this authenticator can process the given credential type.
     */
    public function supports(Credential $credential): bool;

    /**
     * Authenticate and return an Identity, or throw on failure.
     *
     * @throws \Throwable on authentication failure
     */
    public function authenticate(Credential $credential): Identity;
}
