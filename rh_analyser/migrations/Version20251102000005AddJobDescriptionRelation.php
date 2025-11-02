<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add job_description_id foreign key to link candidates to job offers
 */
final class Version20251102000005AddJobDescriptionRelation extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add job_description_id foreign key to candidates table';
    }

    public function up(Schema $schema): void
    {
        // PostgreSQL
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER TABLE candidates ADD job_description_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE candidates ADD CONSTRAINT FK_C8DCD6E4D91F01A6 FOREIGN KEY (job_description_id) REFERENCES job_descriptions (id) ON DELETE SET NULL');
        }
        // MySQL
        elseif ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->addSql('ALTER TABLE candidates ADD COLUMN job_description_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE candidates ADD CONSTRAINT FK_C8DCD6E4D91F01A6 FOREIGN KEY (job_description_id) REFERENCES job_descriptions (id) ON DELETE SET NULL');
        }
        // SQLite
        else {
            $this->addSql('
                CREATE TABLE candidates_new (
                    id VARCHAR(36) NOT NULL,
                    job_description_id INT DEFAULT NULL,
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
                    UNIQUE(username),
                    FOREIGN KEY(job_description_id) REFERENCES job_descriptions (id) ON DELETE SET NULL
                )
            ');

            $this->addSql('
                INSERT INTO candidates_new
                SELECT id, NULL, firstName, lastName, email, username, password, cv_text, cv_file_name,
                       analysis_result, score, created_at, submitted_at, analyzed_at, status,
                       is_active, notification_sent FROM candidates
            ');

            $this->addSql('DROP TABLE candidates');
            $this->addSql('ALTER TABLE candidates_new RENAME TO candidates');
        }
    }

    public function down(Schema $schema): void
    {
        // PostgreSQL
        if ($this->connection->getDatabasePlatform()->getName() === 'postgresql') {
            $this->addSql('ALTER TABLE candidates DROP CONSTRAINT FK_C8DCD6E4D91F01A6');
            $this->addSql('ALTER TABLE candidates DROP COLUMN job_description_id');
        }
        // MySQL
        elseif ($this->connection->getDatabasePlatform()->getName() === 'mysql') {
            $this->addSql('ALTER TABLE candidates DROP FOREIGN KEY FK_C8DCD6E4D91F01A6');
            $this->addSql('ALTER TABLE candidates DROP COLUMN job_description_id');
        }
        // SQLite - would need to recreate table
    }
}
