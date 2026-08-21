<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la devise aux preferences monetaires utilisateur.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if (!$table->hasColumn('money_currency')){
            $this->addSql("ALTER TABLE user_preference ADD money_currency VARCHAR(10) DEFAULT 'EUR' NOT NULL");
        }

        if ($table->hasColumn('money_display_format')){
            $this->addSql("UPDATE user_preference SET money_currency = 'USD', money_display_format = 'dot' WHERE money_display_format = 'us_dollar'");
            $this->addSql("UPDATE user_preference SET money_currency = 'GBP', money_display_format = 'dot' WHERE money_display_format = 'uk_pound'");
            $this->addSql("UPDATE user_preference SET money_currency = 'CHF', money_display_format = 'dot' WHERE money_display_format = 'swiss_franc'");
            $this->addSql("UPDATE user_preference SET money_currency = 'EUR', money_display_format = 'german' WHERE money_display_format = 'german_euro'");
        }
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if ($table->hasColumn('money_currency')){
            $this->addSql('ALTER TABLE user_preference DROP money_currency');
        }
    }
}