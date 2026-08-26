<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822200000 extends AbstractMigration
{
    private const COLUMNS = [
        'show_table_totals' => true,
        'show_balance_table' => true,
        'show_balance_cumulative' => true,
        'show_sub_categories' => true,
        'merge_income_expense_tables' => false,
    ];

    public function getDescription(): string
    {
        return "Ajoute les options d'affichage des tableaux de comptes.";
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        foreach (self::COLUMNS as $column => $default) {
            if (!$table->hasColumn($column)) {
                $this->addSql(sprintf(
                    'ALTER TABLE user_preference ADD %s TINYINT(1) DEFAULT %d NOT NULL',
                    $column,
                    $default ? 1 : 0,
                ));
            }
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        foreach (array_keys(self::COLUMNS) as $column) {
            if ($table->hasColumn($column)) {
                $this->addSql(sprintf('ALTER TABLE user_preference DROP %s', $column));
            }
        }
    }
}
