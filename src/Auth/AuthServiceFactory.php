<?php

declare(strict_types=1);

namespace Compose\Web\Auth;

use Compose\Container\ServiceFactoryInterface;
use Compose\Support\Configuration;
use Psr\Container\ContainerInterface;

/**
 * Factory for AuthService; resolves storage and authenticators from config.
 *
 * Expected config shape:
 * 'auth' => [
 *   'storage' => SessionAuthStorage::class,           // optional, defaults to SessionAuthStorage
 *   'authenticators' => [MyPasswordAuthenticator::class, ...], // optional list
 * ]
 */
final class AuthServiceFactory implements ServiceFactoryInterface
{
    public static function create(ContainerInterface $container, string $name): AuthService
    {
        /** @var Configuration $config */
        $config = $container->get(Configuration::class);
        $authConfig = $config['auth'] ?? [];

        $storageClass = $authConfig['storage'] ?? SessionAuthStorage::class;
        $storage = $container->get($storageClass);

        $authenticatorDefs = $authConfig['authenticators'] ?? [];
        $authenticators = [];
        foreach ($authenticatorDefs as $def) {
            $authenticators[] = \is_string($def) ? $container->get($def) : $def;
        }

        return new AuthService($storage, $authenticators);
    }
}
