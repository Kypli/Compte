<?php

namespace App\DataFixtures;

use App\Entity\Compte as Entity;

use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CompteFixtures extends Fixture implements DependentFixtureInterface, FixtureGroupInterface
{
	public const COMPTE_ADMIN = 'compte_admin';
	public const COMPTE_ADMIN_LIQUIDE = 'compte_admin_liquide';
	public const COMPTE_USER = 'compte_user';

	public function load(ObjectManager $manager): void
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('Compte admin')
			->setMain(true)
			->addUser($this->getReference(UserFixtures::USER_ADMIN))
			->setType($this->getReference(CompteTypeFixtures::COMPTE_COURANT))
		;
		$this->addReference(self::COMPTE_ADMIN, $entity);
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('Compte user')
			->setMain(true)
			->addUser($this->getReference(UserFixtures::USER_USER))
			->setType($this->getReference(CompteTypeFixtures::COMPTE_COURANT))
		;
		$this->addReference(self::COMPTE_USER, $entity);
		$manager->persist($entity);

		// Compte liquide de démonstration, rattaché au même utilisateur que le compte 1.
		$entity = new Entity();
		$entity
			->setLibelle('Espèces')
			->setMain(false)
			->addUser($this->getReference(UserFixtures::USER_ADMIN))
			->setType($this->getReference(CompteTypeFixtures::COMPTE_LIQUIDE))
		;
		$this->addReference(self::COMPTE_ADMIN_LIQUIDE, $entity);
		$manager->persist($entity);

		$manager->flush();
	}

	public static function getGroups(): array
	{
		return ['dev'];
	}

	public function getDependencies(): array
	{
		return [
			UserFixtures::class,
			CompteTypeFixtures::class,
		];
	}
}
