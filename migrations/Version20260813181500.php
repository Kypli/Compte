<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260813181500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les couleurs de fond par page dans les preferences utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if (!$table->hasColumn('dashboard_background')){
            $this->addSql("ALTER TABLE user_preference ADD dashboard_background VARCHAR(20) DEFAULT 'green' NOT NULL");
        }
        if (!$table->hasColumn('account_background')){
            $this->addSql("ALTER TABLE user_preference ADD account_background VARCHAR(20) DEFAULT 'green' NOT NULL");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if ($table->hasColumn('dashboard_background')){
            $this->addSql('ALTER TABLE user_preference DROP dashboard_background');
        }
        if ($table->hasColumn('account_background')){
            $this->addSql('ALTER TABLE user_preference DROP account_background');
        }
    }
}
