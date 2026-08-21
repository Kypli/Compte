<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821200000 extends AbstractMigration
{
    private const ACCOUNT_TYPES = [
        ['Compte libre', 'CL', true],
        ['Compte joint', 'CJ', true],
        ['Compte professionnel', 'PRO', true],
        ['Livret Jeune', 'LJ', false],
        ['Livret de développement durable et solidaire', 'LDDS', false],
        ['Plan épargne logement', 'PEL', false],
        ['Compte épargne logement', 'CEL', false],
        ["Livret d'épargne bancaire", 'LEB', false],
        ['Compte à terme', 'CAT', false],
    ];

    public function getDescription(): string
    {
        return 'Ajoute les types de comptes bancaires les plus courants.';
    }

    public function up(Schema $schema): void
    {
        foreach (self::ACCOUNT_TYPES as [$label, $shortLabel, $overdraftAllowed]) {
            $this->addSql(
                <<<'SQL'
                    INSERT INTO compte_type (libelle, libelle_short, decouvert, taux_interet, plancher, plafond)
                    SELECT :label, :shortLabel, :overdraftAllowed, 0, 0, NULL
                    FROM DUAL
                    WHERE NOT EXISTS (
                        SELECT 1 FROM compte_type WHERE libelle = :lookupLabel
                    )
                    SQL,
                [
                    'label' => $label,
                    'shortLabel' => $shortLabel,
                    'overdraftAllowed' => $overdraftAllowed,
                    'lookupLabel' => $label,
                ],
                [
                    'label' => Types::STRING,
                    'shortLabel' => Types::STRING,
                    'overdraftAllowed' => Types::BOOLEAN,
                    'lookupLabel' => Types::STRING,
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::ACCOUNT_TYPES as [$label]) {
            $this->addSql(
                <<<'SQL'
                    DELETE account_type
                    FROM compte_type account_type
                    LEFT JOIN compte account ON account.type_id = account_type.id
                    WHERE account_type.libelle = :label
                      AND account.id IS NULL
                    SQL,
                ['label' => $label],
                ['label' => Types::STRING],
            );
        }
    }
}
