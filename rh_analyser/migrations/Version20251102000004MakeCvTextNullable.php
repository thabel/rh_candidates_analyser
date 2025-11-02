<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102000004MakeCvTextNullable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make cv_text column nullable since CV submission is now after registration';
    }

    public function up(Schema $schema): void
    {
        // For PostgreSQL
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER TABLE candidates ALTER COLUMN cv_text DROP NOT NULL');
        }
        // For MySQL
        elseif ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->addSql('ALTER TABLE candidates MODIFY cv_text LONGTEXT DEFAULT NULL');
        }
        // For SQLite
        else {
            // SQLite doesn't support direct ALTER COLUMN, so we recreate the table
            $this->addSql('
                CREATE TABLE candidates_new (
                    id VARCHAR(36) NOT NULL,
                    firstName VARCHAR(255) NOT NULL,
                    lastName VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    username VARCHAR(255) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    cv_text CLOB DEFAULT NULL,
                    cv_file_name VARCHAR(255) DEFAULT NULL,
                    analysis_result JSON DEFAULT NULL,
                    score INTEGER DEFAULT NULL,
                    created_at DATETIME NOT NULL,
                    submitted_at DATETIME DEFAULT NULL,
                    analyzed_at DATETIME DEFAULT NULL,
                    status VARCHAR(50) NOT NULL DEFAULT \'pending\',
                    is_active BOOLEAN NOT NULL DEFAULT \'1\',
                    notification_sent BOOLEAN NOT NULL DEFAULT \'0\',
                    PRIMARY KEY(id),
                    UNIQUE(email),
                    UNIQUE(username)
                )
            ');

            $this->addSql('
                INSERT INTO candidates_new
                SELECT id, firstName, lastName, email, username, password, cv_text, cv_file_name,
                       analysis_result, score, created_at, submitted_at, analyzed_at, status,
                       is_active, notification_sent FROM candidates
            ');

            $this->addSql('DROP TABLE candidates');
            $this->addSql('ALTER TABLE candidates_new RENAME TO candidates');
        }
    }

    public function down(Schema $schema): void
    {
        // For rollback - make cv_text NOT NULL again
        // For PostgreSQL
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER TABLE candidates ALTER COLUMN cv_text SET NOT NULL');
        }
        // For MySQL
        elseif ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->addSql('ALTER TABLE candidates MODIFY cv_text LONGTEXT NOT NULL');
        }
        // For SQLite - would need to recreate table again
    }
}
