<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute le journal des actions d'operation et de leurs annulations.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE operation_action (id INT AUTO_INCREMENT NOT NULL, operation_id INT NOT NULL, target_action_id INT DEFAULT NULL, action_type VARCHAR(15) NOT NULL, action_at DATETIME NOT NULL, before_snapshot JSON DEFAULT NULL, after_snapshot JSON DEFAULT NULL, cancelled TINYINT(1) NOT NULL, INDEX IDX_8AEF3B21D9F6D38 (operation_id), INDEX IDX_8AEF3B215A571ED (target_action_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE operation_action ADD CONSTRAINT FK_8AEF3B21D9F6D38 FOREIGN KEY (operation_id) REFERENCES operation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE operation_action ADD CONSTRAINT FK_8AEF3B215A571ED FOREIGN KEY (target_action_id) REFERENCES operation_action (id) ON DELETE SET NULL');
        $this->addSql('INSERT INTO operation_action (operation_id, action_type, action_at, before_snapshot, after_snapshot, cancelled) SELECT id, last_action, date_last_action, undo_snapshot, NULL, 0 FROM operation');
        $this->addSql('ALTER TABLE operation DROP undo_snapshot');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation ADD undo_snapshot JSON DEFAULT NULL');
        $this->addSql('DROP TABLE operation_action');
    }
}
