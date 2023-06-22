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
	public const COMPTE_USER = 'compte_user';

	public function load(ObjectManager $manager)
	{
		// Admin
		$entity = new Entity();
		$entity
			->setLibelle('Compte admin')
			->setMain(true)
			->addUser($this->getReference(UserFixtures::USER_ADMIN))
			->setType($this->getReference('compteType_0'))
		;
		$this->addReference(self::COMPTE_ADMIN, $entity);
		$manager->persist($entity);

		// User
		$entity = new Entity();
		$entity
			->setLibelle('Compte user')
			->setMain(true)
			->addUser($this->getReference(UserFixtures::USER_USER))
			->setType($this->getReference('compteType_0'))
		;
		$this->addReference(self::COMPTE_USER, $entity);
		$manager->persist($entity);

		$manager->flush();
	}

	public static function getGroups(): array
	{
		return ['dev'];
	}

	public function getDependencies()
	{
		return [
			UserFixtures::class,
			CompteTypeFixtures::class,
		];
	}
}
