<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823110000 extends AbstractMigration
{
    private const COLUMNS = [
        'show_table_monthly_average',
        'show_table_percentage',
        'show_annual_gain',
    ];

    public function getDescription(): string
    {
        return "Separe les options de total, moyenne, pourcentage, cumul et gain annuel.";
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        foreach (self::COLUMNS as $column) {
            if (!$table->hasColumn($column)) {
                $this->addSql(sprintf(
                    'ALTER TABLE user_preference ADD %s TINYINT(1) DEFAULT 1 NOT NULL',
                    $column,
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        foreach (self::COLUMNS as $column) {
            if ($table->hasColumn($column)) {
                $this->addSql(sprintf('ALTER TABLE user_preference DROP %s', $column));
            }
        }
    }
}
