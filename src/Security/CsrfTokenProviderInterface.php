<?php

declare(strict_types=1);

namespace Compose\Web\Security;

/**
 * Provides CSRF token generation and validation.
 */
interface CsrfTokenProviderInterface
{
    /**
     * Generate a token for the given form identifier.
     */
    public function generateToken(string $formId): string;

    /**
     * Validate a token for the given form identifier.
     */
    public function validateToken(string $formId, ?string $token): bool;

    /**
     * Get the field name used for CSRF tokens in forms.
     */
    public function getFieldName(): string;
}
