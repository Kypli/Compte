<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812211500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Aligne les index du journal d'actions avec le mapping Doctrine.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation_action RENAME INDEX IDX_8AEF3B21D9F6D38 TO IDX_3A4C35F044AC3583');
        $this->addSql('ALTER TABLE operation_action RENAME INDEX IDX_8AEF3B215A571ED TO IDX_3A4C35F0A9868219');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE operation_action RENAME INDEX IDX_3A4C35F044AC3583 TO IDX_8AEF3B21D9F6D38');
        $this->addSql('ALTER TABLE operation_action RENAME INDEX IDX_3A4C35F0A9868219 TO IDX_8AEF3B215A571ED');
    }
}
