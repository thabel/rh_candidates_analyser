<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251101000001CreateCandidateTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create candidates table for storing job applications (portable across MySQL and PostgreSQL)';
    }

    public function up(Schema $schema): void
    {
       $table = $schema->createTable('candidates');

$table->addColumn('id', Types::STRING, ['length' => 36]);
$table->addColumn('first_name', Types::STRING, ['length' => 255]);
$table->addColumn('last_name', Types::STRING, ['length' => 255]);
$table->addColumn('email', Types::STRING, ['length' => 255]);
$table->addColumn('cv_text', Types::TEXT);
$table->addColumn('cv_file_name', Types::STRING, [
    'length' => 255,
    'notnull' => false,
]);
$table->addColumn('analysis_result', Types::JSON, [
    'notnull' => false,
]);
$table->addColumn('score', Types::INTEGER, [
    'notnull' => false,
]);
$table->addColumn('submitted_at', Types::DATETIME_IMMUTABLE);
$table->addColumn('analyzed_at', Types::DATETIME_IMMUTABLE, [
    'notnull' => false,
]);
$table->addColumn('status', Types::STRING, [
    'length' => 50,
    'default' => 'pending',
]);

$table->setPrimaryKey(['id']);
$table->addUniqueIndex(['email']);
$table->addIndex(['status']);
$table->addIndex(['submitted_at']);

    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('candidates');
    }
}
