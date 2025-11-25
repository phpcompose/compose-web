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
        /** @var AuthService $auth */
        $auth = $this->getContainer()->get(AuthService::class);
        $identity = $auth->currentIdentity();

        if ($identity === null) {
            return new RedirectResponse('/auth/login');
        }

        return [
            'title' => 'User Dashboard',
            'identity' => $identity,
        ];
    }
};
