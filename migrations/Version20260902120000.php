<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute l'option d'affichage des totaux des personnes associées.";
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');
        if (!$table->hasColumn('show_associate_totals')) {
            $this->addSql('ALTER TABLE user_preference ADD show_associate_totals TINYINT(1) DEFAULT 1 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');
        if ($table->hasColumn('show_associate_totals')) {
            $this->addSql('ALTER TABLE user_preference DROP show_associate_totals');
        }
    }
}
