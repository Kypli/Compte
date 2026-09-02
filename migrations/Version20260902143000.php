<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902143000 extends AbstractMigration
{
    private const ACCOUNT_TYPES = [
        ['Compte sur livret', 'CSL', false],
        ['Compte en devises', 'DEV', true],
        ['Carte prépayée', 'CP', false],
        ['Compte titres ordinaire', 'CTO', false],
        ["Plan d'épargne en actions", 'PEA', false],
        ['Assurance-vie', 'AV', false],
        ["Plan d'épargne retraite", 'PER', false],
        ['Compte crypto-actifs', 'CRYPTO', false],
        ['Crédit immobilier', 'CI', false],
        ['Crédit à la consommation', 'CONSO', false],
        ['Crédit renouvelable', 'REV', false],
    ];

    public function getDescription(): string
    {
        return 'Ajoute des types de comptes pour l’épargne, les investissements et les crédits.';
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
