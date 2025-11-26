<?php

declare(strict_types=1);

namespace Compose\Web\Module\User\Repository;

use Compose\Container\ResolvableInterface;
use Compose\Web\Module\User\DTO\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Query\QueryBuilder;

final class DbalUserRepository implements ResolvableInterface
{
    public function __construct(
        private readonly Connection $connection
    ) {
    }

    private function createQueryBuilder() : QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('u.id', 'u.email', 'u.username', 'u.password_hash', 'u.status', 'm.profile', 'm.preferences')
            ->from('cw_users', 'u')
            ->leftJoin('u', 'cw_users_metadata', 'm', 'm.user_id = u.id');
    }

    public function findByEmail(string $email): ?User
    {
        $row = $this->createQueryBuilder()
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->fetchAssociative();

        if (!$row) {
            return null;
        }

        return $this->mapRowToUser($row);
    }

    public function findById(int $id): ?User
    {
        $row = $this->createQueryBuilder()
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->fetchAssociative();

        if (!$row) {
            return null;
        }

        return $this->mapRowToUser($row);
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

    public function updateAdminUser(
        int $userId,
        string $email,
        ?string $username,
        int $status,
        array $profile,
        array $preferences,
        ?string $passwordHash = null
    ): void {
        $fields = [
            'email' => $email,
            'username' => $username,
            'status' => $status,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
        if ($passwordHash !== null) {
            $fields['password_hash'] = $passwordHash;
        }

        $this->connection->update('cw_users', $fields, ['id' => $userId]);
        $this->connection->update('cw_users_metadata', [
            'profile' => json_encode($profile, JSON_UNESCAPED_UNICODE),
            'preferences' => json_encode($preferences, JSON_UNESCAPED_UNICODE),
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ], ['user_id' => $userId]);
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<int,array<string,mixed>>
     */
    public function fetchUsers(array $filters = []): array
    {
        $qb = $this->createQueryBuilder()
            ->addSelect('u.created_at')
            ->orderBy('u.id', 'DESC');

        if (!empty($filters['email'])) {
            $qb->andWhere('u.email LIKE :email')->setParameter('email', '%' . $filters['email'] . '%');
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $qb->andWhere('u.status = :status')->setParameter('status', (int) $filters['status']);
        }

        return $qb->fetchAllAssociative();
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
        if (is_array($value)) {
            return $value;
        }

        if ($value === null || $value === '') {
            return [];
        }

        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $row
     */
    private function mapRowToUser(array $row): User
    {
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
}
