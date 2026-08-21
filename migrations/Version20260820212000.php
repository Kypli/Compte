<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820212000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute l option pour retirer les zeros inutiles des montants.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if (!$table->hasColumn('money_trim_zeros')){
            $this->addSql('ALTER TABLE user_preference ADD money_trim_zeros TINYINT(1) DEFAULT 0 NOT NULL');
        }
        if (!$table->hasColumn('money_show_zero_decimals')){
            $this->addSql('ALTER TABLE user_preference ADD money_show_zero_decimals TINYINT(1) DEFAULT 1 NOT NULL');
        }

        if ($table->hasColumn('money_display_format')){
            $this->addSql("UPDATE user_preference SET money_trim_zeros = 1, money_display_format = 'dot' WHERE money_display_format = 'one_decimal'");
            $this->addSql("UPDATE user_preference SET money_trim_zeros = 1, money_display_format = 'comma' WHERE money_display_format = 'comma_one_decimal'");
            $this->addSql("UPDATE user_preference SET money_trim_zeros = 1, money_display_format = 'euro_cents' WHERE money_display_format = 'euro_one_decimal'");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if ($table->hasColumn('money_trim_zeros')){
            $this->addSql('ALTER TABLE user_preference DROP money_trim_zeros');
        }
        if ($table->hasColumn('money_show_zero_decimals')){
            $this->addSql('ALTER TABLE user_preference DROP money_show_zero_decimals');
        }
    }
}
