<?php

declare(strict_types=1);

namespace Compose\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add contact entries table';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('cw_contact_entries');
        $table->addColumn('id', 'bigint', ['autoincrement' => true]);
        $table->addColumn('form_slug', 'string', ['length' => 64, 'notnull' => true]);
        $table->addColumn('email', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('subject', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('payload', 'json', ['notnull' => true]);
        $table->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['form_slug'], 'cw_contact_entries_form_slug_idx');
        $table->addIndex(['created_at'], 'cw_contact_entries_created_idx');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('cw_contact_entries');
    }
}
