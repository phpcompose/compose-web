<?php

declare(strict_types=1);

namespace Compose\Web\Security;

use Compose\Http\Session\Session;

/**
 * Session-based CSRF token provider that stores
 * generated tokens inside the Compose HTTP session.
 */
final class SessionCsrfTokenProvider implements CsrfTokenProviderInterface
{
    private const SESSION_KEY = '__CSRF_TOKENS__';
    private const DEFAULT_FIELD_NAME = '__CSRF_TOKEN__';

    public function __construct(
        private readonly Session $session,
        private readonly string $fieldName = self::DEFAULT_FIELD_NAME
    ) {
    }

    public function generateToken(string $formId): string
    {
        $token = bin2hex(random_bytes(32));

        $tokens = $this->session->get(self::SESSION_KEY, []);
        $tokens[$formId] = $token;
        $this->session->set(self::SESSION_KEY, $tokens);

        return $token;
    }

    public function validateToken(string $formId, ?string $token): bool
    {
        if ($token === null || $token === '') {
            return false;
        }

        $tokens = $this->session->get(self::SESSION_KEY, []);
        $stored = $tokens[$formId] ?? null;

        if ($stored === null) {
            return false;
        }

        $valid = hash_equals($stored, $token);

        if ($valid) {
            unset($tokens[$formId]);
            if ($tokens === []) {
                $this->session->unset(self::SESSION_KEY);
            } else {
                $this->session->set(self::SESSION_KEY, $tokens);
            }
        }

        return $valid;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }
}
