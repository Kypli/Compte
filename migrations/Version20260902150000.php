<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Conserve l'auteur des dernières actions d'un compte.";
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('operation_action');
        if (!$table->hasColumn('author_id')) {
            $this->addSql('ALTER TABLE operation_action ADD author_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE operation_action ADD CONSTRAINT FK_EBFC907BF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_EBFC907BF675F31B ON operation_action (author_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('operation_action');
        if ($table->hasColumn('author_id')) {
            $this->addSql('ALTER TABLE operation_action DROP FOREIGN KEY FK_EBFC907BF675F31B');
            $this->addSql('ALTER TABLE operation_action DROP author_id');
        }
    }
}
