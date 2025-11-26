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
        $identity = $this->getContainer()->get(AuthService::class)->currentIdentity();
        if ($identity === null) {
            return new RedirectResponse('/auth/login');
        }

        return [
            'title' => 'Users',
            'users' => $this->getContainer()->get(\Compose\Web\Module\User\Repository\DbalUserRepository::class)->fetchUsers(),
        ];
    }
};
