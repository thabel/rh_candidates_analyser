<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251102000003AddCandidateAuthAndNotifications extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add authentication and notification fields to candidates table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE candidates ADD username VARCHAR(255) NOT NULL UNIQUE');
        $this->addSql('ALTER TABLE candidates ADD password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE candidates ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE candidates ADD created_at DATETIME NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE candidates ADD notification_sent TINYINT(1) NOT NULL DEFAULT 0');
        $this->addSql('CREATE INDEX idx_username ON candidates(username)');

        $this->addSql('CREATE TABLE notifications (
            id INT AUTO_INCREMENT NOT NULL,
            candidate_id CHAR(36) NOT NULL,
            title VARCHAR(255) NOT NULL,
            message LONGTEXT NOT NULL,
            score INT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id),
            FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
            INDEX idx_candidate_id (candidate_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notifications');
        $this->addSql('ALTER TABLE candidates DROP COLUMN username');
        $this->addSql('ALTER TABLE candidates DROP COLUMN password');
        $this->addSql('ALTER TABLE candidates DROP COLUMN is_active');
        $this->addSql('ALTER TABLE candidates DROP COLUMN created_at');
        $this->addSql('ALTER TABLE candidates DROP COLUMN notification_sent');
    }
}
