<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute les droits de partage d'un compte et l'attribution des operations.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compte ADD user_roles JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE operation ADD assignee_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE operation ADD CONSTRAINT FK_1981A66D59EC7D60 FOREIGN KEY (assignee_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_1981A66D59EC7D60 ON operation (assignee_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation DROP FOREIGN KEY FK_1981A66D59EC7D60');
        $this->addSql('DROP INDEX IDX_1981A66D59EC7D60 ON operation');
        $this->addSql('ALTER TABLE operation DROP assignee_id');
        $this->addSql('ALTER TABLE compte DROP user_roles');
    }
}
