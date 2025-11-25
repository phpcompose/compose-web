<?php

declare(strict_types=1);

namespace Compose\Web\Module\User;

use Compose\Container\ResolvableInterface;
use Compose\Web\Auth\PasswordHasherInterface;
use Compose\Web\Module\User\DTO\User;
use Compose\Web\Module\User\Repository\DbalUserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class UserService implements UserServiceInterface, ResolvableInterface
{
    public function __construct(
        private readonly DbalUserRepository $users,
        private readonly PasswordHasherInterface $hasher
    ) {
    }

    public function getByEmail(string $email): ?User
    {
        return $this->users->findByEmail($email);
    }

    public function getById(int $id): ?User
    {
        return $this->users->findById($id);
    }

    public function register(string $email, ?string $username, string $plainPassword): int
    {
        $hash = $this->hasher->hash($plainPassword);
        return $this->users->create($email, $username, $hash);
    }

    public function updateEmail(int $userId, string $email): void
    {
        $this->users->updateEmail($userId, $email);
    }

    public function updatePassword(int $userId, string $plainPassword): void
    {
        $hash = $this->hasher->hash($plainPassword);
        $this->users->updatePassword($userId, $hash);
    }
}
