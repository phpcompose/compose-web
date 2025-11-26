<?php

declare(strict_types=1);

namespace Compose\Web\Module\User;

use Compose\Web\Module\User\DTO\User;

interface UserServiceInterface
{
    public function getByEmail(string $email): ?User;
    public function getById(int $id): ?User;

    /**
     * @throws \Doctrine\DBAL\Exception\UniqueConstraintViolationException
     */
    public function register(string $email, ?string $username, string $plainPassword): int;

    /**
     * @throws \Doctrine\DBAL\Exception\UniqueConstraintViolationException
     */
    public function updateEmail(int $userId, string $email): void;

    public function updatePassword(int $userId, string $plainPassword): void;

}
