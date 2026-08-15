<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260814162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le suivi du tutoriel de la page compte dans les preferences utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if (!$table->hasColumn('account_tutorial_seen')){
            $this->addSql('ALTER TABLE user_preference ADD account_tutorial_seen TINYINT DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if ($table->hasColumn('account_tutorial_seen')){
            $this->addSql('ALTER TABLE user_preference DROP account_tutorial_seen');
        }
    }
}
