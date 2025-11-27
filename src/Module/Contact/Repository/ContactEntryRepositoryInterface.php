<?php

declare(strict_types=1);

namespace Compose\Web\Module\Contact\Repository;

interface ContactEntryRepositoryInterface
{
    /**
     * @param array<string,mixed> $values
     */
    public function record(string $formSlug, array $values): int;

    public function setRead(int $id, bool $read): void;

    public function setStarred(int $id, bool $starred): void;

    /**
     * @param array<int,string> $tags
     */
    public function setTags(int $id, array $tags): void;

    /**
     * @return array<int,array<string,mixed>>
     */
    public function fetchRecent(int $limit = 50): array;

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array;
}
