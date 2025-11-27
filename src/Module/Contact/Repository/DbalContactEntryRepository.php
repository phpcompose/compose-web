<?php

declare(strict_types=1);

namespace Compose\Web\Module\Contact\Repository;

use Compose\Container\ResolvableInterface;
use Doctrine\DBAL\Connection;

final class DbalContactEntryRepository implements ContactEntryRepositoryInterface, ResolvableInterface
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param array<string,mixed> $values
     */
    public function record(string $formSlug, array $values): int
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $email = isset($values['email']) && is_string($values['email']) ? $values['email'] : null;
        $subject = isset($values['subject']) && is_string($values['subject']) ? $values['subject'] : null;
        $tags = [];

        $this->connection->insert('cw_contact_entries', [
            'form_slug' => $formSlug,
            'email' => $email,
            'subject' => $subject,
            'payload' => json_encode($values, JSON_UNESCAPED_UNICODE),
            'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
            'is_read' => 0,
            'is_starred' => 0,
            'created_at' => $now,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function setRead(int $id, bool $read): void
    {
        $this->connection->update('cw_contact_entries', ['is_read' => $read ? 1 : 0], ['id' => $id]);
    }

    public function setStarred(int $id, bool $starred): void
    {
        $this->connection->update('cw_contact_entries', ['is_starred' => $starred ? 1 : 0], ['id' => $id]);
    }

    public function setTags(int $id, array $tags): void
    {
        $normalized = array_values(array_filter(array_map('strval', $tags), fn ($tag) => $tag !== ''));
        $this->connection->update('cw_contact_entries', [
            'tags' => json_encode($normalized, JSON_UNESCAPED_UNICODE),
        ], ['id' => $id]);
    }

    public function fetchRecent(int $limit = 50): array
    {
        $limit = max(1, $limit);

        $rows = $this->connection->createQueryBuilder()
            ->select('id', 'form_slug', 'email', 'subject', 'payload', 'tags', 'is_read', 'is_starred', 'created_at')
            ->from('cw_contact_entries')
            ->orderBy('created_at', 'DESC')
            ->addOrderBy('id', 'DESC')
            ->setMaxResults($limit)
            ->fetchAllAssociative();

        return array_map([$this, 'hydrate'], $rows);
    }

    public function find(int $id): ?array
    {
        $row = $this->connection->createQueryBuilder()
            ->select('id', 'form_slug', 'email', 'subject', 'payload', 'tags', 'is_read', 'is_starred', 'created_at')
            ->from('cw_contact_entries')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->fetchAssociative();

        if ($row === false || $row === null) {
            return null;
        }

        return $this->hydrate($row);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate(array $row): array
    {
        $payload = $row['payload'] ?? '{}';
        $decoded = is_array($payload) ? $payload : json_decode((string) $payload, true);

        return [
            'id' => (int) $row['id'],
            'form_slug' => (string) $row['form_slug'],
            'email' => $row['email'] !== null ? (string) $row['email'] : null,
            'subject' => $row['subject'] !== null ? (string) $row['subject'] : null,
            'payload' => is_array($decoded) ? $decoded : [],
            'tags' => $this->decodeTags($row['tags'] ?? null),
            'is_read' => (bool) ($row['is_read'] ?? false),
            'is_starred' => (bool) ($row['is_starred'] ?? false),
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    /**
     * @return array<int,string>
     */
    private function decodeTags(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_map('strval', $value));
        }
        if ($value === null || $value === '') {
            return [];
        }
        $decoded = json_decode((string) $value, true);
        return is_array($decoded) ? array_values(array_map('strval', $decoded)) : [];
    }
}
