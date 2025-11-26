<?php

declare(strict_types=1);

namespace Compose\Web\Auth\Middleware;

use Compose\Container\ResolvableInterface;
use Compose\Support\Configuration;
use Compose\Web\Auth\AclService;
use Compose\Web\Auth\AuthService;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Simple ACL middleware using path/prefix rules from config.
 *
 * Config shape:
 * 'acl' => [
 *   'rules' => [
 *      '/admin' => ['admin'],
 *      '/admin/users' => ['admin'],
 *   ],
 *   'deny_message' => 'Forbidden',
 * ]
 */
final class AclMiddleware implements MiddlewareInterface, ResolvableInterface
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly AclService $acl,
        private readonly Configuration $config
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $rules = $this->config['acl']['rules'] ?? [];
        if (!$rules) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        $required = $this->matchRule($path, $rules);

        if ($required === null) {
            return $handler->handle($request);
        }

        $identity = $this->auth->currentIdentity();
        if ($identity === null || !$this->acl->authorize($identity, $required)) {
            $message = $this->config['acl']['deny_message'] ?? 'Forbidden';
            return new TextResponse($message, 403);
        }

        return $handler->handle($request);
    }

    private function matchRule(string $path, array $rules): array|string|null
    {
        // longest prefix match
        uksort($rules, fn($a, $b) => strlen($b) <=> strlen($a));
        foreach ($rules as $prefix => $roles) {
            $prefix = rtrim($prefix, '/');
            if ($prefix !== '' && str_starts_with($path, $prefix)) {
                return $roles;
            }
        }
        return null;
    }
}
