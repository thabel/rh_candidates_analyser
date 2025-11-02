<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102000003AddCandidateAuthAndNotifications extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authentication and notification fields to candidates table (portable across PostgreSQL/MySQL)';
    }

    public function up(Schema $schema): void
    {
        /** ---------- ALTER candidates TABLE ---------- **/
        $candidates = $schema->getTable('candidates');

        if (!$candidates->hasColumn('username')) {
            $candidates->addColumn('username', Types::STRING, ['length' => 255, 'notnull' => true]);
            $candidates->addUniqueIndex(['username']);
        }

        if (!$candidates->hasColumn('password')) {
            $candidates->addColumn('password', Types::STRING, ['length' => 255, 'notnull' => true]);
        }

        if (!$candidates->hasColumn('is_active')) {
            $candidates->addColumn('is_active', Types::BOOLEAN, ['default' => true]);
            $candidates->addIndex(['is_active']);
        }

        if (!$candidates->hasColumn('created_at')) {
            $candidates->addColumn('created_at', Types::DATETIME_IMMUTABLE, ['default' => 'CURRENT_TIMESTAMP']);
        }

        if (!$candidates->hasColumn('notification_sent')) {
            $candidates->addColumn('notification_sent', Types::BOOLEAN, ['default' => false]);
            $candidates->addIndex(['notification_sent']);
        }

        /** ---------- CREATE notifications TABLE ---------- **/
        if (!$schema->hasTable('notifications')) {
            $notifications = $schema->createTable('notifications');

            $notifications->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
            $notifications->addColumn('candidate_id', Types::STRING, ['length' => 36, 'notnull' => true]);
            $notifications->addColumn('title', Types::STRING, ['length' => 255]);
            $notifications->addColumn('message', Types::TEXT);
            $notifications->addColumn('score', Types::INTEGER);
            $notifications->addColumn('is_read', Types::BOOLEAN, ['default' => false]);
            $notifications->addColumn('created_at', Types::DATETIME_IMMUTABLE);

            $notifications->setPrimaryKey(['id']);
            $notifications->addIndex(['candidate_id']);
            $notifications->addForeignKeyConstraint(
                $schema->getTable('candidates'),
                ['candidate_id'],
                ['id'],
                ['onDelete' => 'CASCADE']
            );
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('notifications')) {
            $schema->dropTable('notifications');
        }

        if ($schema->hasTable('candidates')) {
            $candidates = $schema->getTable('candidates');
            foreach (['username', 'password', 'is_active', 'created_at', 'notification_sent'] as $col) {
                if ($candidates->hasColumn($col)) {
                    $candidates->dropColumn($col);
                }
            }
        }
    }
}
