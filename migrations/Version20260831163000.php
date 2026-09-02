<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute l'ignorance permanente ou temporaire des anomalies d'operations anticipees.";
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('operation');
        if (!$table->hasColumn('anomaly_ignored')) {
            $this->addSql('ALTER TABLE operation ADD anomaly_ignored TINYINT(1) DEFAULT 0 NOT NULL');
        }
        if (!$table->hasColumn('anomaly_ignored_until')) {
            $this->addSql('ALTER TABLE operation ADD anomaly_ignored_until DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('operation');
        if ($table->hasColumn('anomaly_ignored_until')) {
            $this->addSql('ALTER TABLE operation DROP anomaly_ignored_until');
        }
        if ($table->hasColumn('anomaly_ignored')) {
            $this->addSql('ALTER TABLE operation DROP anomaly_ignored');
        }
    }
}
