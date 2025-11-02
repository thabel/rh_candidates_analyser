<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101000002CreateAdminAndJobTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin users and job descriptions tables (portable across MySQL/PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
        /** ---------- ADMINS TABLE ---------- **/
        $admins = $schema->createTable('admins');

        $admins->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $admins->addColumn('username', Types::STRING, ['length' => 255]);
        $admins->addColumn('password', Types::STRING, ['length' => 255]);
        $admins->addColumn('email', Types::STRING, ['length' => 255]);
        $admins->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $admins->addColumn('is_active', Types::BOOLEAN, ['default' => true]);

        $admins->setPrimaryKey(['id']);
        $admins->addUniqueIndex(['username']);
        $admins->addIndex(['is_active']);

        /** ---------- JOB_DESCRIPTIONS TABLE ---------- **/
        $jobs = $schema->createTable('job_descriptions');

        $jobs->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $jobs->addColumn('title', Types::STRING, ['length' => 255]);
        $jobs->addColumn('description', Types::TEXT);
        $jobs->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $jobs->addColumn('updated_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $jobs->addColumn('is_active', Types::BOOLEAN, ['default' => true]);

        $jobs->setPrimaryKey(['id']);
        $jobs->addIndex(['is_active']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('admins');
        $schema->dropTable('job_descriptions');
    }
}
