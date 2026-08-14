<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Integre l'annulation dans l'action d'origine sans ajouter de ligne au journal.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation_action ADD undo_snapshot JSON DEFAULT NULL');
        $this->addSql("UPDATE operation_action target INNER JOIN operation_action cancellation ON cancellation.target_action_id = target.id AND cancellation.action_type = 'undo' SET target.undo_snapshot = cancellation.before_snapshot, target.cancelled = 1");
        $this->addSql("DELETE FROM operation_action WHERE action_type = 'undo'");
        $this->addSql('ALTER TABLE operation_action DROP FOREIGN KEY FK_8AEF3B215A571ED');
        $this->addSql('DROP INDEX IDX_3A4C35F0A9868219 ON operation_action');
        $this->addSql('ALTER TABLE operation_action DROP target_action_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation_action ADD target_action_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_3A4C35F0A9868219 ON operation_action (target_action_id)');
        $this->addSql('ALTER TABLE operation_action ADD CONSTRAINT FK_8AEF3B215A571ED FOREIGN KEY (target_action_id) REFERENCES operation_action (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE operation_action DROP undo_snapshot');
    }
}
