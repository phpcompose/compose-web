<?php

declare(strict_types=1);

use Compose\Web\Auth\Identity;
use Compose\Web\Auth\SessionAuthStorage;
use Compose\Http\Session\Session;
use Compose\Http\Session\NativeSessionStorage;
use PHPUnit\Framework\TestCase;

final class SessionAuthStorageTest extends TestCase
{
    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        session_id(bin2hex(random_bytes(8)));
    }

    public function testStoreAndLoadIdentity(): void
    {
        $session = new Session(new NativeSessionStorage());
        $storage = new SessionAuthStorage($session);

        $identity = new Identity(
            id: 1,
            email: 'user@example.com',
            username: 'user',
            roles: ['admin'],
            profile: ['first_name' => 'Test'],
            preferences: ['dark' => true],
        );

        $storage->store($identity);
        $loaded = $storage->load();

        self::assertNotNull($loaded);
        self::assertSame($identity->getId(), $loaded->getId());
        self::assertSame($identity->getEmail(), $loaded->getEmail());
        self::assertSame($identity->getRoles(), $loaded->getRoles());
        self::assertSame($identity->getProfile(), $loaded->getProfile());
        self::assertSame($identity->getPreferences(), $loaded->getPreferences());
    }

    public function testClearRemovesIdentity(): void
    {
        $session = new Session(new NativeSessionStorage());
        $storage = new SessionAuthStorage($session);

        $identity = new Identity(1, 'user@example.com');
        $storage->store($identity);
        $storage->clear();

        self::assertNull($storage->load());
    }
}
