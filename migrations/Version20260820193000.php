<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalise les anciens formats monetaires avec symbole.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('user_preference');

        if ($table->hasColumn('money_display_format')){
            $this->addSql("UPDATE user_preference SET money_display_format = 'comma' WHERE money_display_format = 'comma_symbol'");
            $this->addSql("UPDATE user_preference SET money_display_format = 'dot' WHERE money_display_format = 'dot_symbol'");
        }
    }

    public function down(Schema $schema): void
    {
    }
}
