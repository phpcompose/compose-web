<?php

declare(strict_types=1);

namespace Compose\Web\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251121163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial auth/user tables';
    }

    public function up(Schema $schema): void
    {
        $users = $schema->createTable('cw_users');
        $users->addColumn('id', 'bigint', ['autoincrement' => true, 'unsigned' => false]);
        $users->setPrimaryKey(['id']);
        $users->addColumn('email', 'string', ['length' => 255, 'notnull' => true]);
        $users->addColumn('username', 'string', ['length' => 64, 'notnull' => false]);
        $users->addColumn('password_hash', 'string', ['length' => 255, 'notnull' => true]);
        $users->addColumn('status', 'integer', ['notnull' => true, 'default' => 1]);
        $users->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $users->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $users->addColumn('last_login_at', 'datetime_immutable', ['notnull' => false]);
        $users->addUniqueIndex(['email'], 'cw_users_email_uq');
        $users->addUniqueIndex(['username'], 'cw_users_username_uq');

        $meta = $schema->createTable('cw_users_metadata');
        $meta->addColumn('user_id', 'bigint', ['notnull' => true]);
        $meta->addColumn('profile', 'json', ['notnull' => true]);
        $meta->addColumn('preferences', 'json', ['notnull' => true]);
        $meta->addColumn('data', 'json', ['notnull' => true]);
        $meta->addColumn('updated_at', 'datetime_immutable', ['notnull' => true]);
        $meta->setPrimaryKey(['user_id']);
        $meta->addForeignKeyConstraint('cw_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_cw_users_metadata_user');

        $roles = $schema->createTable('cw_roles');
        $roles->addColumn('id', 'bigint', ['autoincrement' => true]);
        $roles->setPrimaryKey(['id']);
        $roles->addColumn('slug', 'string', ['length' => 64, 'notnull' => true]);
        $roles->addColumn('name', 'string', ['length' => 128, 'notnull' => true]);
        $roles->addColumn('description', 'text', ['notnull' => false]);
        $roles->addUniqueIndex(['slug'], 'cw_roles_slug_uq');

        $userRoles = $schema->createTable('cw_user_roles');
        $userRoles->addColumn('user_id', 'bigint', ['notnull' => true]);
        $userRoles->addColumn('role_id', 'bigint', ['notnull' => true]);
        $userRoles->addColumn('assigned_at', 'datetime_immutable', ['notnull' => true]);
        $userRoles->setPrimaryKey(['user_id', 'role_id']);
        $userRoles->addForeignKeyConstraint('cw_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_cw_user_roles_user');
        $userRoles->addForeignKeyConstraint('cw_roles', ['role_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_cw_user_roles_role');
        $userRoles->addIndex(['role_id'], 'cw_user_roles_role_idx');

        $tokens = $schema->createTable('cw_access_tokens');
        $tokens->addColumn('id', 'guid', ['notnull' => true]);
        $tokens->addColumn('user_id', 'bigint', ['notnull' => true]);
        $tokens->addColumn('token_hash', 'string', ['length' => 255, 'notnull' => true]);
        $tokens->addColumn('type', 'string', ['length' => 32, 'notnull' => true]);
        $tokens->addColumn('expires_at', 'datetime_immutable', ['notnull' => false]);
        $tokens->addColumn('created_at', 'datetime_immutable', ['notnull' => true]);
        $tokens->setPrimaryKey(['id']);
        $tokens->addUniqueIndex(['token_hash'], 'cw_access_tokens_token_hash_uq');
        $tokens->addIndex(['expires_at'], 'cw_access_tokens_expires_idx');
        $tokens->addForeignKeyConstraint('cw_users', ['user_id'], ['id'], ['onDelete' => 'CASCADE'], 'fk_cw_access_tokens_user');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('cw_access_tokens');
        $schema->dropTable('cw_user_roles');
        $schema->dropTable('cw_roles');
        $schema->dropTable('cw_users_metadata');
        $schema->dropTable('cw_users');
    }
}
