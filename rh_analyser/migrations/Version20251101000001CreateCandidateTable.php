<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101000001CreateCandidateTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create candidates table for storing job applications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE candidates (
            id CHAR(36) NOT NULL COMMENT "(DC2Type:uuid)",
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            cv_text LONGTEXT NOT NULL,
            cv_file_name VARCHAR(255) DEFAULT NULL,
            analysis_result JSON DEFAULT NULL,
            score INT DEFAULT NULL,
            submitted_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
            analyzed_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)",
            status VARCHAR(50) NOT NULL DEFAULT "pending",
            PRIMARY KEY(id),
            UNIQUE KEY UNIQ_E4B5FA50E7927C74 (email),
            INDEX idx_status (status),
            INDEX idx_submitted_at (submitted_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE candidates');
    }
}
