<?php

declare(strict_types=1);

namespace Compose\Web\Module\User\Repository;

use Compose\Web\Module\User\DTO\User;
use Doctrine\DBAL\Connection;
use Compose\Container\ResolvableInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

final class DbalUserRepository implements ResolvableInterface
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

    public function findById(int $id): ?User
    {
        $row = $this->connection->createQueryBuilder()
            ->select('u.id', 'u.email', 'u.username', 'u.password_hash', 'u.status', 'm.profile', 'm.preferences')
            ->from('cw_users', 'u')
            ->leftJoin('u', 'cw_users_metadata', 'm', 'm.user_id = u.id')
            ->where('u.id = :id')
            ->setParameter('id', $id)
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

    public function create(string $email, ?string $username, string $passwordHash): int
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        try {
            $this->connection->insert('cw_users', [
                'email' => $email,
                'username' => $username,
                'password_hash' => $passwordHash,
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
                'last_login_at' => null,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            throw $e;
        }

        $userId = (int) $this->connection->lastInsertId();

        $this->connection->insert('cw_users_metadata', [
            'user_id' => $userId,
            'profile' => '{}',
            'preferences' => '{}',
            'data' => '{}',
            'updated_at' => $now,
        ]);

        return $userId;
    }

    public function updateEmail(int $userId, string $email): void
    {
        $this->connection->update('cw_users', [
            'email' => $email,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ], ['id' => $userId]);
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $this->connection->update('cw_users', [
            'password_hash' => $passwordHash,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ], ['id' => $userId]);
    }
}
