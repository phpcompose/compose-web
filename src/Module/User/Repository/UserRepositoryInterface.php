<?php

declare(strict_types=1);

namespace Compose\Web\Module\User\Repository;

use Compose\Web\Module\User\DTO\User;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;
}
