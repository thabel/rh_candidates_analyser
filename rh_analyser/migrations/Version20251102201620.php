<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251102201620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('DROP INDEX idx_a2e0150f1b5771dd');
        $this->addSql('ALTER TABLE admins ALTER is_active DROP DEFAULT');
        $this->addSql('DROP INDEX idx_6a77f80c1b5771dd');
        $this->addSql('DROP INDEX idx_6a77f80c2f86193');
        $this->addSql('DROP INDEX idx_6a77f80c3182c73c');
        $this->addSql('DROP INDEX idx_6a77f80c7b00651c');
        $this->addSql('ALTER TABLE candidates ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE candidates ALTER is_active DROP DEFAULT');
        $this->addSql('ALTER TABLE candidates ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE candidates ALTER notification_sent DROP DEFAULT');
        $this->addSql('DROP INDEX idx_c60d3b991b5771dd');
        $this->addSql('ALTER TABLE job_descriptions ALTER is_active DROP DEFAULT');
        $this->addSql('ALTER TABLE notifications ALTER is_read DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE notifications ALTER is_read SET DEFAULT false');
        $this->addSql('ALTER TABLE admins ALTER is_active SET DEFAULT true');
        $this->addSql('CREATE INDEX idx_a2e0150f1b5771dd ON admins (is_active)');
        $this->addSql('ALTER TABLE candidates ALTER created_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE candidates ALTER status SET DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE candidates ALTER is_active SET DEFAULT true');
        $this->addSql('ALTER TABLE candidates ALTER notification_sent SET DEFAULT false');
        $this->addSql('CREATE INDEX idx_6a77f80c1b5771dd ON candidates (is_active)');
        $this->addSql('CREATE INDEX idx_6a77f80c2f86193 ON candidates (notification_sent)');
        $this->addSql('CREATE INDEX idx_6a77f80c3182c73c ON candidates (submitted_at)');
        $this->addSql('CREATE INDEX idx_6a77f80c7b00651c ON candidates (status)');
        $this->addSql('ALTER TABLE job_descriptions ALTER is_active SET DEFAULT true');
        $this->addSql('CREATE INDEX idx_c60d3b991b5771dd ON job_descriptions (is_active)');
    }
}
