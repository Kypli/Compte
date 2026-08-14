<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute l'etat necessaire pour annuler la derniere action d'une operation.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation ADD undo_snapshot JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation DROP undo_snapshot');
    }
}
