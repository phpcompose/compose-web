<?php

declare(strict_types=1);

namespace Compose\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121171500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tags and state flags to contact entries';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('cw_contact_entries');
        $table->addColumn('tags', 'json', ['notnull' => false]);
        $table->addColumn('is_read', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('is_starred', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addIndex(['is_read'], 'cw_contact_entries_is_read_idx');
        $table->addIndex(['is_starred'], 'cw_contact_entries_is_starred_idx');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('cw_contact_entries');
        $table->dropColumn('tags');
        $table->dropColumn('is_read');
        $table->dropColumn('is_starred');
    }
}
