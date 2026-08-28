<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute l'option pour ancrer les lignes de totaux des tableaux de comptes.";
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');
        if (!$table->hasColumn('anchor_table_totals')) {
            $this->addSql('ALTER TABLE user_preference ADD anchor_table_totals TINYINT(1) DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');
        if ($table->hasColumn('anchor_table_totals')) {
            $this->addSql('ALTER TABLE user_preference DROP anchor_table_totals');
        }
    }
}
