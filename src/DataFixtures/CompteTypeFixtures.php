<?php

namespace App\DataFixtures;

use App\Entity\CompteType as Entity;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;

class CompteTypeFixtures extends Fixture implements FixtureGroupInterface
{
	public function load(ObjectManager $manager)
	{
		// Datas
		$datas = [
			[
				"libelle" => "Compte courant",
				"libelleShort" => "CC",
				"decouvert" => true,
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
			$manager->persist($entity);
		}
		$manager->flush();
	}

	public static function getGroups(): array
	{
		return ['dev', 'prod'];
	}
}
