<?php

declare(strict_types=1);

namespace Compose\Web\Auth\Middleware;

use Compose\Container\ResolvableInterface;
use Compose\Web\Auth\AuthService;
use Compose\Support\Configuration;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Guards configured path prefixes, requiring an authenticated identity.
 * Exempt paths bypass the guard. If not authenticated, redirects to login
 * with an optional redirect query param.
 */
final class AuthGuardMiddleware implements MiddlewareInterface, ResolvableInterface
{
    public function __construct(
        private readonly AuthService $auth,
        private readonly Configuration $config
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        $guardConfig = $this->config['auth']['guard'] ?? ['protected' => [], 'exempt' => []];
        $protected = $guardConfig['protected'] ?? [];
        $exempt = $guardConfig['exempt'] ?? [];

        if ($this->isExempt($path, $exempt)) {
            return $handler->handle($request);
        }

        if ($this->isProtected($path, $protected)) {
            if ($this->auth->currentIdentity() === null) {
                $login = $this->config['auth']['login_url'] ?? '/auth/login';
                $param = $this->config['auth']['login_redirect_param'] ?? 'redirect';
                $redirectTo = $login;
                if ($param !== null) {
                    $redirectTo .= '?' . urlencode($param) . '=' . urlencode($path);
                }
                return new RedirectResponse($redirectTo);
            }
        }

        return $handler->handle($request);
    }

    private function isProtected(string $path, array $protected): bool
    {
        foreach ($protected as $prefix) {
            if ($this->pathStartsWith($path, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function isExempt(string $path, array $exempt): bool
    {
        foreach ($exempt as $prefix) {
            if ($this->pathStartsWith($path, $prefix)) {
                return true;
            }
        }
        return false;
    }

    private function pathStartsWith(string $path, string $prefix): bool
    {
        $prefix = rtrim($prefix, '/');
        return $prefix === '' ? false : str_starts_with($path, $prefix);
    }
}
