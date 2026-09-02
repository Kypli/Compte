<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901191000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Normalise les noms des index de proprietaire et de credit pour Doctrine.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compte RENAME INDEX IDX_COMPTE_OWNER TO IDX_CFF652607E3C61F9');
        $this->addSql('ALTER TABLE credit RENAME INDEX IDX_CREDIT_USER TO IDX_1CC16EFEA76ED395');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE compte RENAME INDEX IDX_CFF652607E3C61F9 TO IDX_COMPTE_OWNER');
        $this->addSql('ALTER TABLE credit RENAME INDEX IDX_1CC16EFEA76ED395 TO IDX_CREDIT_USER');
    }
}
