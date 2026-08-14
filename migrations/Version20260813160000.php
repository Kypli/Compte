<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les preferences visuelles du tableau et le deplacement annulable des categories.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_preference ADD table_palette VARCHAR(20) DEFAULT 'classic' NOT NULL, ADD show_editable_border TINYINT DEFAULT 1 NOT NULL");
        $this->addSql('ALTER TABLE operation_action ADD category_id INT DEFAULT NULL, CHANGE operation_id operation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE operation_action ADD CONSTRAINT FK_3A4C35F012469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3A4C35F012469DE2 ON operation_action (category_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM operation_action WHERE action_type = 'move'");
        $this->addSql('ALTER TABLE operation_action DROP FOREIGN KEY FK_3A4C35F012469DE2');
        $this->addSql('DROP INDEX IDX_3A4C35F012469DE2 ON operation_action');
        $this->addSql('ALTER TABLE operation_action DROP category_id, CHANGE operation_id operation_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_preference DROP table_palette, DROP show_editable_border');
    }
}
