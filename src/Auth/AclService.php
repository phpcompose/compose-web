<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

final class AclService
{
    /**
     * @param array<string> $superRoles
     */
    public function __construct(
        private readonly array $superRoles = ['admin']
    ) {
    }

    /**
     * @param string|string[] $requiredRoles
     */
    public function authorize(Identity $identity, string|array $requiredRoles): bool
    {
        $required = (array) $requiredRoles;
        if ($required === []) {
            return true;
        }

        $userRoles = $identity->getRoles();

        foreach ($this->superRoles as $super) {
            if (in_array($super, $userRoles, true)) {
                return true;
            }
        }

        foreach ($required as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
