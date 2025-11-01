<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101000002CreateAdminAndJobTables extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create admin users and job descriptions tables';
    }

    public function up(Schema $schema): void
    {
        // Create admins table
        $this->addSql('CREATE TABLE admins (
            id INT AUTO_INCREMENT NOT NULL,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY(id),
            UNIQUE KEY UNIQ_F8B01D77F85E0677 (username),
            INDEX idx_is_active (is_active)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');

        // Create job_descriptions table
        $this->addSql('CREATE TABLE job_descriptions (
            id INT AUTO_INCREMENT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT "(DC2Type:datetime_immutable)",
            updated_at DATETIME DEFAULT NULL COMMENT "(DC2Type:datetime_immutable)",
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY(id),
            INDEX idx_is_active (is_active)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE admins');
        $this->addSql('DROP TABLE job_descriptions');
    }
}
