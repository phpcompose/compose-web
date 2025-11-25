<?php

use Compose\Container\ContainerAwareInterface;
use Compose\Container\ContainerAwareTrait;
use Compose\Web\Auth\AuthService;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ServerRequestInterface;

return new class implements ContainerAwareInterface {
    use ContainerAwareTrait;

    public function __invoke(ServerRequestInterface $request): array|RedirectResponse
    {
        return [
            'title' => 'User Dashboard',
            'identity' => $this->getContainer()->get(AuthService::class)->currentIdentity(),
        ];
    }
};
