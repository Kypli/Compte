<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901134000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute les champs de valorisation des biens immobiliers et mobiliers.";
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('immobilier')) {
            $table = $schema->getTable('immobilier');
            if (!$table->hasColumn('user_id')) {
                $this->addSql('ALTER TABLE immobilier ADD user_id INT DEFAULT NULL');
                $this->addSql('CREATE INDEX IDX_IMMOBILIER_USER ON immobilier (user_id)');
                $this->addSql('ALTER TABLE immobilier ADD CONSTRAINT FK_IMMOBILIER_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
            }
            if (!$table->hasColumn('libelle')) {
                $this->addSql("ALTER TABLE immobilier ADD libelle VARCHAR(160) DEFAULT 'Bien immobilier' NOT NULL");
            }
            if (!$table->hasColumn('valeur')) {
                $this->addSql('ALTER TABLE immobilier ADD valeur DOUBLE PRECISION DEFAULT 0 NOT NULL');
            }
            if (!$table->hasColumn('adresse')) {
                $this->addSql('ALTER TABLE immobilier ADD adresse VARCHAR(255) DEFAULT NULL');
            }
            if (!$table->hasColumn('surface')) {
                $this->addSql('ALTER TABLE immobilier ADD surface DOUBLE PRECISION DEFAULT NULL');
            }
            if (!$table->hasColumn('description')) {
                $this->addSql('ALTER TABLE immobilier ADD description LONGTEXT DEFAULT NULL');
            }
        }

        if ($schema->hasTable('mobilier')) {
            $table = $schema->getTable('mobilier');
            if (!$table->hasColumn('user_id')) {
                $this->addSql('ALTER TABLE mobilier ADD user_id INT DEFAULT NULL');
                $this->addSql('CREATE INDEX IDX_MOBILIER_USER ON mobilier (user_id)');
                $this->addSql('ALTER TABLE mobilier ADD CONSTRAINT FK_MOBILIER_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
            }
            if (!$table->hasColumn('libelle')) {
                $this->addSql("ALTER TABLE mobilier ADD libelle VARCHAR(160) DEFAULT 'Bien mobilier' NOT NULL");
            }
            if (!$table->hasColumn('valeur')) {
                $this->addSql('ALTER TABLE mobilier ADD valeur DOUBLE PRECISION DEFAULT 0 NOT NULL');
            }
            if (!$table->hasColumn('categorie')) {
                $this->addSql('ALTER TABLE mobilier ADD categorie VARCHAR(80) DEFAULT NULL');
            }
            if (!$table->hasColumn('description')) {
                $this->addSql('ALTER TABLE mobilier ADD description LONGTEXT DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('immobilier')) {
            $table = $schema->getTable('immobilier');
            if ($table->hasForeignKey('FK_IMMOBILIER_USER')) {
                $this->addSql('ALTER TABLE immobilier DROP FOREIGN KEY FK_IMMOBILIER_USER');
            }
            if ($table->hasIndex('IDX_IMMOBILIER_USER')) {
                $this->addSql('DROP INDEX IDX_IMMOBILIER_USER ON immobilier');
            }
            foreach (['description', 'surface', 'adresse', 'valeur', 'libelle', 'user_id'] as $column) {
                if ($table->hasColumn($column)) {
                    $this->addSql(sprintf('ALTER TABLE immobilier DROP %s', $column));
                }
            }
        }

        if ($schema->hasTable('mobilier')) {
            $table = $schema->getTable('mobilier');
            if ($table->hasForeignKey('FK_MOBILIER_USER')) {
                $this->addSql('ALTER TABLE mobilier DROP FOREIGN KEY FK_MOBILIER_USER');
            }
            if ($table->hasIndex('IDX_MOBILIER_USER')) {
                $this->addSql('DROP INDEX IDX_MOBILIER_USER ON mobilier');
            }
            foreach (['description', 'categorie', 'valeur', 'libelle', 'user_id'] as $column) {
                if ($table->hasColumn($column)) {
                    $this->addSql(sprintf('ALTER TABLE mobilier DROP %s', $column));
                }
            }
        }
    }
}
