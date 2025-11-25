<?php

declare(strict_types=1);

use Compose\Web\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHashTest extends TestCase
{
    public function testPassword123HashMatches(): void
    {
        $hasher = new PasswordHasher();
        $hash = '$2y$12$uKDSr9O9XZp2GtKMW5OW6esfYRTwUxtFRAPRdHQNXg6H0PR/tF3Q.';

        self::assertTrue($hasher->verify('password123', $hash));
        self::assertFalse($hasher->verify('wrong', $hash));
    }
}
