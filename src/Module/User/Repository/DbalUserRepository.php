<?php

declare(strict_types=1);

namespace Compose\Web\Module\User\Repository;

use Compose\Web\Module\User\DTO\User;
use Doctrine\DBAL\Connection;
use Compose\Container\ResolvableInterface;

final class DbalUserRepository implements UserRepositoryInterface, ResolvableInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->connection->createQueryBuilder()
            ->select('u.id', 'u.email', 'u.username', 'u.password_hash', 'u.status', 'm.profile', 'm.preferences')
            ->from('cw_users', 'u')
            ->leftJoin('u', 'cw_users_metadata', 'm', 'm.user_id = u.id')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->fetchAssociative();

        if (!$row) {
            return null;
        }

        $roles = $this->fetchRoles((int) $row['id']);

        return new User(
            id: (int) $row['id'],
            email: (string) $row['email'],
            username: $row['username'] !== null ? (string) $row['username'] : null,
            passwordHash: (string) $row['password_hash'],
            status: (int) $row['status'],
            roles: $roles,
            profile: $this->decodeJson($row['profile'] ?? null),
            preferences: $this->decodeJson($row['preferences'] ?? null),
        );
    }

    /**
     * @return array<string>
     */
    private function fetchRoles(int $userId): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('r.slug')
            ->from('cw_user_roles', 'ur')
            ->innerJoin('ur', 'cw_roles', 'r', 'r.id = ur.role_id')
            ->where('ur.user_id = :user_id')
            ->setParameter('user_id', $userId)
            ->fetchAllAssociative();

        return array_values(array_map(fn (array $r) => (string) $r['slug'], $rows));
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJson(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
