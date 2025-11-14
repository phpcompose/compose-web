<?php

declare(strict_types=1);

use Compose\Web\Validation\Validator\EmailAddress;
use PHPUnit\Framework\TestCase;

final class EmailAddressTest extends TestCase
{
    public function testAcceptsValidEmail(): void
    {
        $validator = new EmailAddress();
        self::assertNull($validator('user@example.com'));
    }

    public function testRejectsInvalidEmail(): void
    {
        $validator = new EmailAddress();
        self::assertSame('Invalid email address', $validator('not-email'));
    }

    public function testUsesCustomMessage(): void
    {
        $validator = new EmailAddress('Please enter a valid email');
        self::assertSame('Please enter a valid email', $validator('nope'));
    }
}
