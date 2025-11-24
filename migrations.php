<?php

declare(strict_types=1);

return [
    'table_storage' => [
        'table_name' => 'cw_migration_versions',
        'version_column_name' => 'version',
        'executed_at_column_name' => 'executed_at',
    ],
    'migrations_paths' => [
        'Compose\\Web\\Migrations' => 'database/migrations',
    ],
    'all_or_nothing' => true,
    'transactional' => true,
];
