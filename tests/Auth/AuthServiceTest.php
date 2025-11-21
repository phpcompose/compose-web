<?php

declare(strict_types=1);

use Compose\Web\Auth\AuthService;
use Compose\Web\Auth\AuthStorageInterface;
use Compose\Web\Auth\AuthenticatorInterface;
use Compose\Web\Auth\Credential;
use Compose\Web\Auth\Identity;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    public function testAuthenticateStoresIdentity(): void
    {
        $identity = new Identity(99, 'tester@example.com', roles: ['user']);

        $authenticator = new class($identity) implements AuthenticatorInterface {
            public function __construct(private Identity $identity) {}
            public function supports(Credential $credential): bool { return $credential->getType() === 'password'; }
            public function authenticate(Credential $credential): Identity { return $this->identity; }
        };

        $storage = new class implements AuthStorageInterface {
            public ?Identity $stored = null;
            public function store(Identity $identity): void { $this->stored = $identity; }
            public function clear(): void { $this->stored = null; }
            public function load(): ?Identity { return $this->stored; }
        };

        $service = new AuthService($storage, [$authenticator]);
        $result = $service->authenticate(new Credential('password', 'tester@example.com', 'secret'));

        self::assertTrue($service->hasIdentity());
        self::assertSame($identity, $service->currentIdentity());
        self::assertSame($identity, $result);
        self::assertSame($identity, $storage->load());
    }

    public function testThrowsWhenNoAuthenticatorSupportsCredential(): void
    {
        $this->expectException(\RuntimeException::class);
        $service = new AuthService(
            new class implements AuthStorageInterface {
                public function store(Identity $identity): void {}
                public function clear(): void {}
                public function load(): ?Identity { return null; }
            },
            [] // no authenticators
        );

        $service->authenticate(new Credential('password', 'user@example.com', 'secret'));
    }
}
