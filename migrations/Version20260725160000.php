<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les jetons de reinitialisation de mot de passe.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD password_reset_token VARCHAR(64) DEFAULT NULL, ADD password_reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649375FB77 ON `user` (password_reset_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649375FB77 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP password_reset_token, DROP password_reset_token_expires_at');
    }
}
