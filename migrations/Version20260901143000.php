<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Normalise le schema Doctrine apres les champs patrimoine.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE immobilier CHANGE libelle libelle VARCHAR(160) NOT NULL, CHANGE valeur valeur DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE immobilier RENAME INDEX idx_immobilier_user TO IDX_142D24D2A76ED395');
        $this->addSql('ALTER TABLE mobilier CHANGE libelle libelle VARCHAR(160) NOT NULL, CHANGE valeur valeur DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE mobilier RENAME INDEX idx_mobilier_user TO IDX_125BDA84A76ED395');
        $this->addSql('ALTER TABLE operation CHANGE anomaly_ignored anomaly_ignored TINYINT NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE password_reset_token_expires_at password_reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649a0360754 TO UNIQ_8D93D64976F5C865');
        $this->addSql('ALTER TABLE user RENAME INDEX uniq_8d93d649375fb77 TO UNIQ_8D93D6496B7BA4B6');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE immobilier CHANGE libelle libelle VARCHAR(160) DEFAULT 'Bien immobilier' NOT NULL, CHANGE valeur valeur DOUBLE PRECISION DEFAULT 0 NOT NULL");
        $this->addSql('ALTER TABLE immobilier RENAME INDEX IDX_142D24D2A76ED395 TO idx_immobilier_user');
        $this->addSql("ALTER TABLE mobilier CHANGE libelle libelle VARCHAR(160) DEFAULT 'Bien mobilier' NOT NULL, CHANGE valeur valeur DOUBLE PRECISION DEFAULT 0 NOT NULL");
        $this->addSql('ALTER TABLE mobilier RENAME INDEX IDX_125BDA84A76ED395 TO idx_mobilier_user');
        $this->addSql('ALTER TABLE operation CHANGE anomaly_ignored anomaly_ignored TINYINT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE password_reset_token_expires_at password_reset_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE user RENAME INDEX UNIQ_8D93D64976F5C865 TO uniq_8d93d649a0360754');
        $this->addSql('ALTER TABLE user RENAME INDEX UNIQ_8D93D6496B7BA4B6 TO uniq_8d93d649375fb77');
    }
}
