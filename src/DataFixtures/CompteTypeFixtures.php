<?php

namespace App\DataFixtures;

use App\Entity\CompteType as Entity;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class CompteTypeFixtures extends Fixture implements FixtureGroupInterface
{
	public const COMPTE_COURANT = 'compte_type_compte_courant';
	public const COMPTE_LIQUIDE = 'compte_type_compte_liquide';

	public function load(ObjectManager $manager): void
	{
		// Datas
		$datas = [
			[
				"libelle" => "Compte courant",
				"libelleShort" => "CC",
				"reference" => self::COMPTE_COURANT,
				"decouvert" => true,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Compte libre",
				"libelleShort" => "CL",
				"decouvert" => true,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Compte liquide",
				"libelleShort" => "ESP",
				"reference" => self::COMPTE_LIQUIDE,
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Compte joint",
				"libelleShort" => "CJ",
				"decouvert" => true,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Compte professionnel",
				"libelleShort" => "PRO",
				"decouvert" => true,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Livret Jeune",
				"libelleShort" => "LJ",
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Livret de développement durable et solidaire",
				"libelleShort" => "LDDS",
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Plan épargne logement",
				"libelleShort" => "PEL",
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Compte épargne logement",
				"libelleShort" => "CEL",
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Livret d'épargne bancaire",
				"libelleShort" => "LEB",
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Compte à terme",
				"libelleShort" => "CAT",
				"decouvert" => false,
				"tauxInteret" => 0,
				"plancher" => 0,
				"plafond" => null,
			],
			[
				"libelle" => "Livret A",
				"libelleShort" => "LA",
				"decouvert" => false,
				"tauxInteret" => 3,
				"plancher" => 10,
				"plafond" => 22950,
			],
			[
				"libelle" => "Livret B",
				"libelleShort" => "LB",
				"decouvert" => false,
				"tauxInteret" => 0.5,
				"plancher" => 10,
				"plafond" => null,
			],
			[
				"libelle" => "Livret d'épargne populaire",
				"libelleShort" => "LEP",
				"decouvert" => false,
				"tauxInteret" => 5.6,
				"plancher" => 10,
				"plafond" => 7700,
			],
		];

		// Save
		foreach ($datas as $key => $value){

			$entity = new Entity();
			$entity
				->setLibelle($value['libelle'])
				->setLibelleShort($value['libelleShort'])
				->setDecouvert($value['decouvert'])
				->setTauxInteret($value['tauxInteret'])
				->setPlancher($value['plancher'])
				->setPlafond($value['plafond'])
			;

			$this->addReference('compteType_'.$key, $entity);
			if (isset($value['reference'])) {
				$this->addReference($value['reference'], $entity);
			}
			$manager->persist($entity);
		}
		$manager->flush();
	}

	public static function getGroups(): array
	{
		return ['dev', 'prod'];
	}
}
