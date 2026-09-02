<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute le proprietaire des comptes et le suivi detaille des credits.";
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('compte') && !$schema->getTable('compte')->hasColumn('owner_id')) {
            $this->addSql('ALTER TABLE compte ADD owner_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_COMPTE_OWNER ON compte (owner_id)');
            $this->addSql('ALTER TABLE compte ADD CONSTRAINT FK_COMPTE_OWNER FOREIGN KEY (owner_id) REFERENCES `user` (id) ON DELETE SET NULL');
            if ($schema->hasTable('compte_user')) {
                $this->addSql('UPDATE compte c INNER JOIN (SELECT compte_id, MIN(user_id) AS user_id FROM compte_user GROUP BY compte_id) first_user ON first_user.compte_id = c.id SET c.owner_id = first_user.user_id WHERE c.owner_id IS NULL');
            }
        }

        if (!$schema->hasTable('credit')) {
            $this->addSql('CREATE TABLE credit (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, libelle VARCHAR(160) NOT NULL, organisme VARCHAR(120) DEFAULT NULL, type VARCHAR(30) NOT NULL, montant_initial DOUBLE PRECISION NOT NULL, capital_restant DOUBLE PRECISION NOT NULL, taux_annuel DOUBLE PRECISION DEFAULT NULL, mensualite DOUBLE PRECISION NOT NULL, assurance_mensuelle DOUBLE PRECISION DEFAULT NULL, date_debut DATE DEFAULT NULL, date_fin DATE DEFAULT NULL, actif TINYINT(1) NOT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_CREDIT_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE credit ADD CONSTRAINT FK_CREDIT_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');

            return;
        }

        $credit = $schema->getTable('credit');
        if (!$credit->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE credit ADD user_id INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_CREDIT_USER ON credit (user_id)');
            $this->addSql('ALTER TABLE credit ADD CONSTRAINT FK_CREDIT_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        }
        if (!$credit->hasColumn('libelle')) {
            $this->addSql("ALTER TABLE credit ADD libelle VARCHAR(160) DEFAULT 'Credit' NOT NULL");
            $this->addSql('ALTER TABLE credit CHANGE libelle libelle VARCHAR(160) NOT NULL');
        }
        if (!$credit->hasColumn('organisme')) {
            $this->addSql('ALTER TABLE credit ADD organisme VARCHAR(120) DEFAULT NULL');
        }
        if (!$credit->hasColumn('type')) {
            $this->addSql("ALTER TABLE credit ADD type VARCHAR(30) DEFAULT 'autre' NOT NULL");
            $this->addSql('ALTER TABLE credit CHANGE type type VARCHAR(30) NOT NULL');
        }
        if (!$credit->hasColumn('montant_initial')) {
            $this->addSql('ALTER TABLE credit ADD montant_initial DOUBLE PRECISION DEFAULT 0 NOT NULL');
            $this->addSql('ALTER TABLE credit CHANGE montant_initial montant_initial DOUBLE PRECISION NOT NULL');
        }
        if (!$credit->hasColumn('capital_restant')) {
            $this->addSql('ALTER TABLE credit ADD capital_restant DOUBLE PRECISION DEFAULT 0 NOT NULL');
            $this->addSql('ALTER TABLE credit CHANGE capital_restant capital_restant DOUBLE PRECISION NOT NULL');
        }
        if (!$credit->hasColumn('taux_annuel')) {
            $this->addSql('ALTER TABLE credit ADD taux_annuel DOUBLE PRECISION DEFAULT NULL');
        }
        if (!$credit->hasColumn('mensualite')) {
            $this->addSql('ALTER TABLE credit ADD mensualite DOUBLE PRECISION DEFAULT 0 NOT NULL');
            $this->addSql('ALTER TABLE credit CHANGE mensualite mensualite DOUBLE PRECISION NOT NULL');
        }
        if (!$credit->hasColumn('assurance_mensuelle')) {
            $this->addSql('ALTER TABLE credit ADD assurance_mensuelle DOUBLE PRECISION DEFAULT NULL');
        }
        if (!$credit->hasColumn('date_debut')) {
            $this->addSql('ALTER TABLE credit ADD date_debut DATE DEFAULT NULL');
        }
        if (!$credit->hasColumn('date_fin')) {
            $this->addSql('ALTER TABLE credit ADD date_fin DATE DEFAULT NULL');
        }
        if (!$credit->hasColumn('actif')) {
            $this->addSql('ALTER TABLE credit ADD actif TINYINT(1) DEFAULT 1 NOT NULL');
            $this->addSql('ALTER TABLE credit CHANGE actif actif TINYINT(1) NOT NULL');
        }
        if (!$credit->hasColumn('notes')) {
            $this->addSql('ALTER TABLE credit ADD notes LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('credit')) {
            $credit = $schema->getTable('credit');
            if ($credit->hasForeignKey('FK_CREDIT_USER')) {
                $this->addSql('ALTER TABLE credit DROP FOREIGN KEY FK_CREDIT_USER');
            }
            if ($credit->hasIndex('IDX_CREDIT_USER')) {
                $this->addSql('DROP INDEX IDX_CREDIT_USER ON credit');
            }
            foreach (['notes', 'actif', 'date_fin', 'date_debut', 'assurance_mensuelle', 'mensualite', 'taux_annuel', 'capital_restant', 'montant_initial', 'type', 'organisme', 'libelle', 'user_id'] as $column) {
                if ($credit->hasColumn($column)) {
                    $this->addSql(sprintf('ALTER TABLE credit DROP %s', $column));
                }
            }
        }

        if ($schema->hasTable('compte')) {
            $compte = $schema->getTable('compte');
            if ($compte->hasForeignKey('FK_COMPTE_OWNER')) {
                $this->addSql('ALTER TABLE compte DROP FOREIGN KEY FK_COMPTE_OWNER');
            }
            if ($compte->hasIndex('IDX_COMPTE_OWNER')) {
                $this->addSql('DROP INDEX IDX_COMPTE_OWNER ON compte');
            }
            if ($compte->hasColumn('owner_id')) {
                $this->addSql('ALTER TABLE compte DROP owner_id');
            }
        }
    }
}
