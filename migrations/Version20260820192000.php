<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820192000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le format d affichage monetaire aux preferences utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if (!$table->hasColumn('money_display_format')){
            $this->addSql("ALTER TABLE user_preference ADD money_display_format VARCHAR(30) DEFAULT 'comma' NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if ($table->hasColumn('money_display_format')){
            $this->addSql('ALTER TABLE user_preference DROP money_display_format');
        }
    }
}
